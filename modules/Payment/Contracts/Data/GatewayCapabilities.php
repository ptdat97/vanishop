<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

final readonly class GatewayCapabilities
{
    public function __construct(
        public bool $callbacks = false,
        public bool $query = false,
        public bool $refund = false,
        public bool $partialRefund = false,
        /** Nhân viên xác nhận đã nhận tiền (chuyển khoản thủ công). */
        public bool $manualConfirmation = false,
        /** Thời gian chờ thanh toán (giây); null = không hết hạn (COD). */
        public ?int $paymentTtlSeconds = null,
    ) {}
}
