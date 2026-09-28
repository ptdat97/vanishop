<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\GatewayResult;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Shared\Domain\Money\Money;

/**
 * Extension point (tag `vani.payment.gateways`). Core: `cod`, `manual_bank_transfer`. Plugin: VietQR, VNPay, MoMo…
 * Bộ contract test: Modules\Payment\Testing\PaymentGatewayContract.
 *
 * - initiate() chạy SAU commit và phải idempotent (gọi lại cho cùng payment trả cùng kết quả/giao dịch).
 * - verifyCallback() chỉ xác minh + chuẩn hoá; Core mới là nơi ghi nhận (unique transaction, so số tiền).
 * - refund() idempotent theo $idempotencyKey.
 */
interface PaymentGateway
{
    public const TAG = 'vani.payment.gateways';

    public function code(): string;

    public function label(): string;

    public function capabilities(): GatewayCapabilities;

    public function isAvailable(PaymentContext $context): bool;

    public function initiate(PaymentData $payment): PaymentInitiation;

    /**
     * @throws InvalidCallback
     */
    public function verifyCallback(Request $request): GatewayCallback;

    public function query(PaymentData $payment): GatewayStatus;

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult;
}
