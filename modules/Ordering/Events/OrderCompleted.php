<?php

declare(strict_types=1);

namespace Modules\Ordering\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Đơn hoàn tất: đã giao hết và quá hạn đổi trả (`vani:fulfillment:complete-orders`). Từ đây doanh thu không còn
 * bị đảo do trả hàng thông thường — loyalty (điểm khả dụng), hoá đơn điện tử, hoa hồng creator lắng nghe.
 */
final readonly class OrderCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $orderId,
        public string $publicId,
        public string $number,
        public ?int $customerId,
        public int $total,
        public string $currency,
    ) {}
}
