<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class RefundCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $refundId, public int $paymentId, public int $orderId, public int $amount, public string $status, public ?int $brandId = null) {}
}
