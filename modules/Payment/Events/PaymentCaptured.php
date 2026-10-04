<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class PaymentCaptured implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  string|null  $paymentPublicId  0.3.17 — định danh công khai (tích hợp dùng thay id nội bộ)
     */
    public function __construct(public int $paymentId, public int $orderId, public int $amount, public string $gatewayCode, public ?string $paymentPublicId = null) {}
}
