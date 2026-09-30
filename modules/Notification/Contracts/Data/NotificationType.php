<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts\Data;

/**
 * Loại tin (`order_placed`, `cart_abandoned`…) + biến dùng được trong mẫu + mẫu mặc định theo kênh.
 * Mẫu trong DB (Admin → Thông báo) luôn thắng mẫu mặc định.
 */
final readonly class NotificationType
{
    /**
     * @param  list<string>  $variables
     * @param  array<string, array{subject?: ?string, body?: ?string, meta?: array<string, mixed>|null}>  $defaults  kênh => mẫu
     */
    public function __construct(
        public string $code,
        public string $label,
        public array $variables = [],
        public array $defaults = [],
    ) {}
}
