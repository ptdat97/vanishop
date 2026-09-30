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
        /**
         * Thu tiền khi giao hàng (COD và tương tự): đơn bắt đầu ở `cod_pending`, được tự xác nhận (nếu bật),
         * vận đơn đầu tiên mang số tiền thu hộ, tiền ghi nhận khi hãng báo giao thành công.
         */
        public bool $collectsOnDelivery = false,
    ) {}
}
