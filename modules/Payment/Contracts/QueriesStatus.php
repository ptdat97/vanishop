<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Shared\Contracts\PluginApi;

/**
 * Interface tuỳ chọn cho PaymentGateway: tra cứu giao dịch ở cổng. Core dùng khi không nhận được IPN
 * (`vani:payment:reconcile`) và khi đối soát khoản đã thu (`vani:payment:verify`). Không xác định được → `Pending`.
 */
#[PluginApi]
interface QueriesStatus
{
    public function query(PaymentData $payment): GatewayStatus;
}
