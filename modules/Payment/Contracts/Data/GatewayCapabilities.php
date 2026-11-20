<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Thông số của cổng. Hành vi (nhận callback, tra cứu, hoàn tiền, giữ tiền) không khai ở đây mà bằng interface tuỳ chọn:
 * `ReceivesCallbacks`, `QueriesStatus`, `RefundsPayments`, `CapturesLater`.
 */
#[PluginApi]
final readonly class GatewayCapabilities
{
    public function __construct(
        /** Hoàn một phần được (chỉ có nghĩa khi cổng `RefundsPayments`). */
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
