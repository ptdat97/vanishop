<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\RefundResult;
use Modules\Shared\Contracts\PluginApi;

/**
 * Interface tuỳ chọn cho cổng hoàn bất đồng bộ: tra trạng thái lệnh hoàn đã gửi. `vani:payment:refunds` gọi cho các khoản
 * hoàn còn "đang hoàn"; không có interface này thì Core gọi lại `refund()` cùng khoá idempotency.
 */
#[PluginApi]
interface TracksRefunds extends RefundsPayments
{
    public function refundStatus(PaymentData $payment, string $idempotencyKey, ?string $gatewayReference): RefundResult;
}
