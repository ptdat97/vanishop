<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\GatewayResult;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Interface tuỳ chọn cho PaymentGateway: hoàn tiền qua cổng. Không có → khoản hoàn chờ nhân viên/`RefundPayout` chuyển trả.
 * Hoàn một phần chỉ khi `GatewayCapabilities::$partialRefund`. Core gọi sau commit; idempotent theo `$idempotencyKey`.
 */
#[PluginApi]
interface RefundsPayments
{
    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult;
}
