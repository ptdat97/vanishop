<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\ReturnResult;
use Modules\Shared\Contracts\PluginApi;

/**
 * Interface tuỳ chọn cho PaymentGateway: cổng chuyển khách về Return URL chung của Core
 * (`PaymentData::$returnUrl` = `/payments/{code}/return`). Cổng đọc + xác minh tham số trả về; Core tìm khoản thanh toán,
 * đưa khách về trang đơn kèm thông báo. Return URL **không** ghi nhận thanh toán — chỉ callback/IPN đã xác minh mới ghi nhận.
 */
#[PluginApi]
interface HandlesReturn
{
    public function parseReturn(Request $request): ReturnResult;
}
