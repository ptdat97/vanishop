<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Cổng đã giữ tiền (chưa thu). Đơn được xác nhận; tiền thu sau qua CapturesLater.
 */
final readonly class PaymentAuthorized implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $paymentId, public int $orderId, public int $amount, public string $gatewayCode) {}
}
