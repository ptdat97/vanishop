<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class RefundCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  string|null  $refundPublicId  0.3.17
     * @param  string|null  $paymentPublicId  0.3.17
     */
    public function __construct(public int $refundId, public int $paymentId, public int $orderId, public int $amount, public ?string $refundPublicId = null, public ?string $paymentPublicId = null) {}
}
