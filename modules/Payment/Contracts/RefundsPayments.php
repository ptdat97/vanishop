<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\RefundResult;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Interface tuỳ chọn cho PaymentGateway: hoàn tiền qua cổng. Không có → khoản hoàn chờ nhân viên/`RefundPayout` chuyển trả.
 * Hoàn một phần chỉ khi `GatewayCapabilities::$partialRefund`. Core gọi sau commit; **idempotent theo `$idempotencyKey`**
 * (Core có thể gọi lại cùng key khi lần trước chưa có kết quả).
 *
 * Kết quả: `Succeeded` → đã hoàn; `Pending` → cổng đã nhận lệnh, Core hỏi lại sau (`TracksRefunds`, hoặc gọi lại `refund()`
 * cùng key); `Failed` → chờ hoàn tay. Không bao giờ trả `Succeeded` khi tiền chưa đi.
 */
#[PluginApi]
interface RefundsPayments
{
    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): RefundResult;
}
