<?php

declare(strict_types=1);

namespace Modules\Returns\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Đã hoàn tất đổi/trả (đã nhập kho hàng bán được, đã tạo hoàn tiền). Listener: Integration, hoá đơn điều chỉnh (plugin).
 */
final readonly class ReturnResolved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $returnId, public int $orderId, public int $refundedAmount) {}
}
