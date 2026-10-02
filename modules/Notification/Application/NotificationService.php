<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Contracts\Customers;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\NotificationCatalog;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Notification\Contracts\Notifier;
use Modules\Notification\Domain\TemplateRenderer;
use Modules\Notification\Persistence\Models\NotificationLog;
use Modules\Notification\Persistence\Models\NotificationTemplate;

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
    ) {}

    public function notify(NotificationRequest $request): array
    {
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
     * Template đang bật theo kênh. Thiếu locale → 'vi'.
     *
     * @return array<string, NotificationTemplate> channel => template
     */
    private function templates(NotificationRequest $request): array
    {
        $rows = NotificationTemplate::query()
            ->where('type', $request->type)
            ->where('active', true)
            ->whereIn('locale', array_unique([$request->recipient->locale, 'vi']))
            ->get()
            ->sortBy(fn (NotificationTemplate $template): int => $template->locale === $request->recipient->locale ? 0 : 1);

        $picked = [];
        foreach ($rows as $template) {
            $picked[$template->channel] ??= $template;
        }

        // Kênh chưa có mẫu trong DB → mẫu mặc định do Core/plugin khai báo (NotificationCatalog).
        foreach ($this->catalog->types()[$request->type]->defaults ?? [] as $channel => $default) {
            $picked[$channel] ??= new NotificationTemplate([
                'type' => $request->type, 'channel' => $channel, 'locale' => 'vi',
                'subject' => $default['subject'] ?? null, 'body' => $default['body'] ?? null, 'meta' => $default['meta'] ?? null, 'active' => true,
            ]);
        }

        return $picked;
    }

    private function record(NotificationRequest $request, NotificationChannel $channel, NotificationTemplate $template): ?NotificationLog
    {
        $skip = $this->consentMissing($request, $channel->code());
        $key = mb_substr("{$request->key}:{$channel->code()}", 0, 160);

        $inserted = DB::table('notification_logs')->insertOrIgnore([
            'idempotency_key' => $key,
            'type' => $request->type,
            'category' => $request->category,
            'channel' => $channel->code(),
            'template_id' => $template->id,
            'customer_id' => $request->recipient->customerId,
            'recipient' => $this->address($request->recipient, $channel->code()),
            'subject' => TemplateRenderer::render($template->subject, $request->variables),
            'body' => TemplateRenderer::render($template->body, $request->variables),
            'meta' => $template->meta === null ? null : json_encode(TemplateRenderer::renderArray($template->meta, $request->variables), JSON_UNESCAPED_UNICODE),
            'status' => $skip ? NotificationLog::SKIPPED : NotificationLog::QUEUED,
            'error' => $skip ? 'consent.missing' : null,
            'correlation_id' => Context::get('correlation_id'),
            'created_at' => now(),
        ]);

        return $inserted === 1 ? NotificationLog::query()->where('idempotency_key', $key)->first() : null;
    }

    /**
     * Tin marketing: bắt buộc consent theo kênh (mail ↔ consent `email`).
     */
    private function consentMissing(NotificationRequest $request, string $channel): bool
    {
        if ($request->category !== NotificationRequest::MARKETING) {
            return false;
        }

        return $request->recipient->customerId === null
            || ! $this->customers->hasConsent($request->recipient->customerId, $channel === 'mail' ? 'email' : $channel, 'marketing');
    }

    private function address(Recipient $recipient, string $channel): string
    {
        return (string) ($channel === 'mail' ? $recipient->email : $recipient->phone);
    }
}
