<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Shared\Contracts\PluginApi;

/**
 * Interface tuỳ chọn cho PaymentGateway: cổng gửi callback/IPN về `/api/payments/{code}/callback`. Không có → route 404.
 *
 * verifyCallback() chỉ xác minh chữ ký + chuẩn hoá; Core mới là nơi ghi nhận (unique transaction, so số tiền).
 */
#[PluginApi]
interface ReceivesCallbacks
{
    /**
     * @throws InvalidCallback
     */
    public function verifyCallback(Request $request): GatewayCallback;
}
