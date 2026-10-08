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

    /**
     * @param  string|null  $publicId  0.3.17
     * @param  string|null  $number  0.3.17
     * @param  int|null  $replacementOrderId  đơn thay thế khi đổi hàng (0.3.32)
     */
    public function __construct(public int $returnId, public int $orderId, public int $refundedAmount, public ?string $publicId = null, public ?string $number = null, public ?int $replacementOrderId = null) {}
}
