<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Callback/IPN đã xác minh chữ ký, chuẩn hoá. `acknowledgement` là phản hồi cổng mong đợi (JSON).
 */
final readonly class GatewayCallback
{
    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const PENDING = 'pending';

    /** Đã giữ tiền, chưa thu (chỉ cổng CapturesLater). */
    public const AUTHORIZED = 'authorized';

    /**
     * @param  array<string, mixed>  $maskedPayload  payload đã che dữ liệu nhạy cảm, để lưu vết
     * @param  array<string, mixed>  $acknowledgement
     */
    public function __construct(
        public string $paymentPublicId,
        public string $gatewayTransactionId,
        public string $status,
        public Money $amount,
        public array $maskedPayload = [],
        public array $acknowledgement = ['ok' => true],
    ) {}
}
