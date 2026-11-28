<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\Customers;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\NotificationCatalog;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Notification\Contracts\Notifier;
use Modules\Notification\Domain\TemplateRenderer;
use Modules\Notification\Persistence\Models\NotificationLog;
use Modules\Notification\Persistence\Models\NotificationTemplate;
use Modules\Shared\Support\StoreLocale;

/**
 * Chọn template cho từng kênh, render, ghi nhật ký rồi xếp hàng gửi. Render lúc xếp hàng
 * để mọi lần thử gửi cùng một nội dung.
 */
final class NotificationService implements Notifier
{
    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly Customers $customers,
        private readonly NotificationCatalog $catalog,
        private readonly CustomerAccounts $accounts,
    ) {}

    public function notify(NotificationRequest $request): array
    {
        $request = vani_filter('vani.service.notification.request', $request);
        $available = $this->channels->all();
        $queued = [];

        foreach ($this->templates($request) as $channelCode => $template) {
            $channel = $available[$channelCode] ?? null;
            if ($channel === null || ! $channel->canReach($request->recipient)) {
                continue;
            }

            $log = $this->record($request, $channel, $template);
            if ($log === null) {
                continue; // đã gửi/xếp hàng trước đó với cùng key
            }

            if ($log->status === NotificationLog::QUEUED) {
                SendNotificationJob::dispatch($log->id)->afterCommit();
                $queued[] = $channelCode;
            }
        }

        return $queued;
    }

    /**
     * Template đang bật theo kênh. Thiếu locale → ngôn ngữ mặc định của cửa hàng.
     *
     * @return array<string, NotificationTemplate> channel => template
     */
    private function templates(NotificationRequest $request): array
    {
        $locale = $request->recipient->locale ?? StoreLocale::default();
        $rows = NotificationTemplate::query()
            ->where('type', $request->type)
            ->where('active', true)
            ->whereIn('locale', array_unique([$locale, StoreLocale::default()]))
            ->get()
            ->sortBy(fn (NotificationTemplate $template): int => $template->locale === $locale ? 0 : 1);

        $picked = [];
        foreach ($rows as $template) {
            $picked[$template->channel] ??= $template;
        }

        // Kênh chưa có mẫu trong DB → mẫu mặc định do Core/plugin khai báo (NotificationCatalog).
        foreach ($this->catalog->types()[$request->type]->defaults ?? [] as $channel => $default) {
            $picked[$channel] ??= new NotificationTemplate([
                'type' => $request->type, 'channel' => $channel, 'locale' => StoreLocale::default(),
                // Mẫu mặc định có thể là khoá dịch (khai báo lúc boot, ADR-049).
                'subject' => isset($default['subject']) ? __($default['subject'], [], StoreLocale::default()) : null,
                'body' => isset($default['body']) ? __($default['body'], [], StoreLocale::default()) : null,
                'meta' => $default['meta'] ?? null, 'active' => true,
            ]);
        }

        return $picked;
    }

    private function record(NotificationRequest $request, NotificationChannel $channel, NotificationTemplate $template): ?NotificationLog
    {
        $skipReason = $this->skipReason($request->category, $channel->code(), $request->recipient->customerId, $request->recipient->email);
        $key = mb_substr("{$request->key}:{$channel->code()}", 0, 160);
        $variables = $this->variables($request, $channel->code(), $skipReason !== null);
        $body = TemplateRenderer::render($template->body, $variables);
        if (isset($variables['unsubscribe_url']) && $body !== null && ! str_contains($body, (string) $variables['unsubscribe_url'])) {
            $body = rtrim($body)."\n\n—\n".__('notification::messages.unsubscribe_footer', ['url' => $variables['unsubscribe_url']], $request->recipient->locale ?? StoreLocale::default());
        }

        $inserted = DB::table('notification_logs')->insertOrIgnore([
            'idempotency_key' => $key,
            'type' => $request->type,
            'category' => $request->category,
            'channel' => $channel->code(),
            'template_id' => $template->id,
            'customer_id' => $request->recipient->customerId,
            'recipient' => $this->address($request->recipient, $channel->code()),
            'subject' => TemplateRenderer::render($template->subject, $variables),
            'body' => $body,
            'meta' => $template->meta === null ? null : json_encode(TemplateRenderer::renderArray($template->meta, $variables), JSON_UNESCAPED_UNICODE),
            'status' => $skipReason === null ? NotificationLog::QUEUED : NotificationLog::SKIPPED,
            'error' => $skipReason,
            'correlation_id' => Context::get('correlation_id'),
            'created_at' => now(),
        ]);

        return $inserted === 1 ? NotificationLog::query()->where('idempotency_key', $key)->first() : null;
    }

    /**
     * Biến của mẫu. Tin marketing gửi qua email có thêm `unsubscribe_url` (link huỷ nhận tin, Nghị định 91/2020); mẫu không
     * đặt biến này thì Core tự thêm một dòng cuối email — tin marketing không bao giờ thiếu cách huỷ.
     *
     * @return array<string, scalar|null>
     */
    private function variables(NotificationRequest $request, string $channel, bool $skipped): array
    {
        $variables = $request->variables;
        if (! $skipped && $request->category === NotificationRequest::MARKETING && $channel === 'mail' && $request->recipient->customerId !== null) {
            $variables['unsubscribe_url'] = $this->accounts->unsubscribeUrl($request->recipient->customerId, 'email');
        }

        return $variables;
    }

    /**
     * Tin marketing: bắt buộc consent theo kênh (mail ↔ consent `email`).
     */
    public function skipReason(string $category, string $channel, ?int $customerId, ?string $email): ?string
    {
        if ($category !== NotificationRequest::MARKETING) {
            return null;
        }
        if ($customerId === null || ! $this->customers->hasConsent($customerId, $channel === 'mail' ? 'email' : $channel, 'marketing')) {
            return 'consent.missing';
        }
        if ($channel === 'mail') {
            $customer = $this->customers->find($customerId);
            if (! $customer?->emailVerified || $customer->email === null || $email === null || strcasecmp($customer->email, $email) !== 0) {
                return 'email.unverified';
            }
        }

        return null;
    }

    private function address(Recipient $recipient, string $channel): string
    {
        return (string) ($channel === 'mail' ? $recipient->email : $recipient->phone);
    }
}
