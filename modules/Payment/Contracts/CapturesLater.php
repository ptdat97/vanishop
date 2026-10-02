<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\GatewayResult;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Shared\Domain\Money\Money;

/**
 * Interface bổ sung tuỳ chọn cho PaymentGateway (ADR-030 W4b): cổng **giữ tiền** (authorize, callback status
 * `authorized`) rồi **thu** sau (capture) — thẻ quốc tế, BNPL. Core kiểm tra bằng `instanceof`.
 *
 * Core thu khi vận đơn rời kho (`vanishop.payment.capture_on = shipped`) hoặc nhân viên bấm thu; đơn huỷ khi đang giữ
 * tiền → `void`. Cả hai idempotent theo `$idempotencyKey` (gọi lại không thu/huỷ hai lần).
 */
interface CapturesLater
{
    public function capture(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult;

    public function void(PaymentData $payment, string $idempotencyKey): GatewayResult;
}
