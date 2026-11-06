<?php

declare(strict_types=1);

namespace Modules\Ordering\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Nhân viên đổi trạng thái đơn ngoài trình tự (0.3.99) — chỉ nhảy tới trước, có lý do. Module khác dọn hệ quả: vd. tới
 * `completed` → Fulfillment huỷ vận đơn còn mở và chốt hàng đang giữ (hàng coi như đã giao ngoài hệ thống).
 */
final readonly class OrderStatusOverridden implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $orderId,
        public string $publicId,
        public string $reservationKey,
        public string $from,
        public string $to,
        public string $reason,
    ) {}
}
