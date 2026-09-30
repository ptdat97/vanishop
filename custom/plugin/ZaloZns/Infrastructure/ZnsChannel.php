<?php

declare(strict_types=1);

namespace Plugin\ZaloZns\Infrastructure;

use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;

/**
 * Kênh `zns`: ZNS không nhận nội dung tự do — mẫu tin kênh `zns` trong Admin khai báo
 * `meta = {"template_id": "<id Zalo duyệt>", "params": {"<tên tham số ZNS>": "{{ bien }}"}}`.
 */
final class ZnsChannel implements NotificationChannel
{
    public function __construct(private readonly ZnsClient $client) {}

    public function code(): string
    {
        return 'zns';
    }

    public function canReach(Recipient $recipient): bool
    {
        return $recipient->phone !== null && $recipient->phone !== '';
    }

    public function send(OutgoingMessage $message): SendResult
    {
        $templateId = (string) ($message->meta['template_id'] ?? '');
        if ($templateId === '' || $message->recipient->phone === null) {
            return SendResult::permanent('zns.template_missing');
        }

        return $this->client->send($message->recipient->phone, $templateId, (array) ($message->meta['params'] ?? []), "vani-{$message->logId}");
    }
}
