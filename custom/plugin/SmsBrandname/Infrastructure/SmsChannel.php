<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname\Infrastructure;

use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Tenancy\Contracts\Settings;
use Plugin\SmsBrandname\SmsBrandnameServiceProvider;

/**
 * Kênh `sms` cho Notification: nội dung = body của template (nên viết không dấu, đúng mẫu đã đăng ký).
 */
final class SmsChannel implements NotificationChannel
{
    public function __construct(
        private readonly EsmsClient $client,
        private readonly Settings $settings,
        private readonly string $defaultBrandname,
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

        return $this->client->send($message->recipient->phone, (string) $message->body, $this->brandname(), $message->idempotencyKey);
    }

    /**
     * Brandname: cấu hình `vani.sms-brandname.brandname` (Admin → Cấu hình) → brandname trong config.
     */
    public function brandname(): string
    {
        $configured = (string) $this->settings->get(SmsBrandnameServiceProvider::ID, 'brandname', '');

        return $configured !== '' ? $configured : $this->defaultBrandname;
    }
}
