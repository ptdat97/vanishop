<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Callback/IPN đã xác minh chữ ký, chuẩn hoá. `acknowledgement` là phản hồi cổng mong đợi (JSON).
 */
#[PluginApi]
final readonly class GatewayCallback
{
    /**
     * @param  array<string, mixed>  $maskedPayload  payload đã che dữ liệu nhạy cảm, để lưu vết
     * @param  array<string, mixed>  $acknowledgement
     */
    public function __construct(
        public string $paymentPublicId,
        public string $gatewayTransactionId,
        public GatewayPaymentStatus $status,
        public Money $amount,
        public array $maskedPayload = [],
        public array $acknowledgement = ['ok' => true],
    ) {}
}
