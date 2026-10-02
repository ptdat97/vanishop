<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts\Data;

/**
 * Tin đã render xong, gửi cho kênh. `meta` là tham số riêng của kênh đã render biến (vd. ZNS: template_id, params).
 */
final readonly class OutgoingMessage
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public int $logId,
        public string $idempotencyKey,
        public string $type,
        public Recipient $recipient,
        public ?string $subject,
        public ?string $body,
        public array $meta,
        public int $attempt,
    ) {}
}
