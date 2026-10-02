<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts\Data;

final readonly class NotificationRequest
{
    public const TRANSACTIONAL = 'transactional';

    public const MARKETING = 'marketing';

    /**
     * @param  string  $key  khoá idempotency của sự việc (vd. "order_placed:123"); cùng key + kênh chỉ gửi một lần
     * @param  array<string, scalar|null>  $variables  biến cho template: {{ order_number }}…
     */
    public function __construct(
        public string $type,
        public string $key,
        public Recipient $recipient,
        public array $variables,
        public string $category = self::TRANSACTIONAL,
    ) {}
}
