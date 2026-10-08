<?php

declare(strict_types=1);

namespace Modules\Ordering\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Huỷ một phần đơn (0.3.18): số lượng + tiền của các dòng đã giảm, hàng giữ của phần huỷ đã nhả. Listener tiêu biểu:
 * dựng lại vận đơn chưa rời kho (Fulfillment), hoàn phần đã thu hoặc giảm tiền thu hộ COD (Payment), tích hợp.
 */
final readonly class OrderLinesCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<array{order_line_id: int, variant_id: int, quantity: int, amount: int}>  $lines  phần huỷ (amount = thành tiền sau giảm giá)
     * @param  string  $cancellationId  định danh lần huỷ (ULID) — khoá idempotency cho hoàn tiền
     * @param  int  $amount  tổng đơn giảm (ròng) = Σ amount của dòng − `$promotionClawback` — số tiền cần hoàn/giảm thu hộ
     * @param  string  $cause  `shop` | `customer` (0.3.31)
     * @param  int  $promotionClawback  khuyến mãi thu hồi vì phần còn lại không đủ điều kiện (chỉ khi `customer`, 0.3.31)
     */
    public function __construct(
        public int $orderId,
        public string $publicId,
        public string $cancellationId,
        public array $lines,
        public int $amount,
        public string $reason,
        public string $source,
        public string $cause = 'shop',
        public int $promotionClawback = 0,
    ) {}
}
