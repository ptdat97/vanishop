<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname\Infrastructure;

use Modules\Brand\Contracts\BrandDirectory;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;
use Plugin\SmsBrandname\SmsBrandnameServiceProvider;

/**
 * Kênh `sms` cho Notification: nội dung = body của template (nên viết không dấu, đúng mẫu đã đăng ký).
 */
final class SmsChannel implements NotificationChannel
{
    /**
     * @param  array<string, string>  $brandnames  (dự phòng, config cũ) mã brand => brandname
     */
    public function __construct(
        private readonly EsmsClient $client,
        private readonly BrandDirectory $brands,
        private readonly Settings $settings,
        private readonly string $defaultBrandname,
        private readonly array $brandnames = [],
    ) {}

    public function code(): string
    {
        return 'sms';
    }

    public function canReach(Recipient $recipient): bool
    {
        return $recipient->phone !== null && $recipient->phone !== '';
    }

    public function send(OutgoingMessage $message): SendResult
    {
        if ($message->recipient->phone === null || trim((string) $message->body) === '') {
            return SendResult::permanent('sms.empty');
        }

        return $this->client->send($message->recipient->phone, (string) $message->body, $this->brandnameFor($message->brandId), $message->idempotencyKey);
    }

    /**
     * Brandname: cấu hình `vani.sms-brandname.brandname` theo brand (Admin → Cấu hình) → config cũ theo mã brand →
     * brandname mặc định.
     */
    public function brandnameFor(?int $brandId): string
    {
        $scope = $brandId === null ? SettingsScope::owner() : SettingsScope::brand($brandId);
        $configured = (string) $this->settings->get(SmsBrandnameServiceProvider::ID, 'brandname', $scope, '');
        if ($configured !== '') {
            return $configured;
        }

        $code = $brandId === null ? null : $this->brands->find($brandId)?->code;

        return $code !== null && isset($this->brandnames[$code]) ? $this->brandnames[$code] : $this->defaultBrandname;
    }
}
