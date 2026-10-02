<?php

declare(strict_types=1);

namespace Modules\Ordering\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Listener tiêu biểu: nhả giữ hàng + hoàn lượt khuyến mãi (Checkout), hoàn tiền (Payment).
 */
final readonly class OrderCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $orderId,
        public string $publicId,
        public string $reservationKey,
        public string $reason,
        public string $source,
    ) {}
}
