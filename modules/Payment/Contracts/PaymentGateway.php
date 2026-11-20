<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Shared\Contracts\PluginApi;

/**
 * Extension point (tag `vani.payment.gateways`). Bắt buộc ≥ 1 đang bật (ADR-029). Mọi cổng là plugin: `cod`, `manual_bank_transfer` (plugin hệ thống `vani.cod`, `vani.bank-transfer`), VietQR, VNPay, MoMo…
 * Bộ contract test: Modules\Payment\Testing\PaymentGatewayContract.
 *
 * Interface lõi chỉ gồm việc mọi cổng đều có. Khả năng khác là interface tuỳ chọn, Core kiểm tra bằng `instanceof`:
 * `ReceivesCallbacks` (IPN), `CallbackResponder` (định dạng phản hồi IPN), `QueriesStatus` (tra cứu), `RefundsPayments`
 * (hoàn qua cổng), `CapturesLater` (giữ tiền rồi thu).
 *
 * - initiate() chạy SAU commit và phải idempotent (gọi lại cho cùng payment trả cùng kết quả/giao dịch).
 */
#[PluginApi]
interface PaymentGateway
{
    public const TAG = 'vani.payment.gateways';

    public function code(): string;

    public function label(): string;

    public function capabilities(): GatewayCapabilities;

    public function isAvailable(PaymentContext $context): bool;

    public function initiate(PaymentData $payment): PaymentInitiation;
}
