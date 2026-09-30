<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname\Infrastructure;

use Modules\Brand\Contracts\BrandDirectory;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;

/**
 * Kênh `sms` cho Notification: nội dung = body của template (nên viết không dấu, đúng mẫu đã đăng ký).
 */
final class SmsChannel implements NotificationChannel
{
    /**
     * @param  array<string, string>  $brandnames  mã brand => brandname
     */
    public function __construct(
        private readonly EsmsClient $client,
        private readonly BrandDirectory $brands,
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

    public function brandnameFor(?int $brandId): string
    {
        $code = $brandId === null ? null : $this->brands->find($brandId)?->code;

        return $code !== null && isset($this->brandnames[$code]) ? $this->brandnames[$code] : $this->defaultBrandname;
    }
}
