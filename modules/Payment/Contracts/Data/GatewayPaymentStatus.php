<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Trạng thái giao dịch phía cổng đã chuẩn hoá (callback/IPN, tra cứu). Core ghi nhận theo giá trị này.
 */
#[PluginApi]
enum GatewayPaymentStatus: string
{
    case Paid = 'paid';
    case Failed = 'failed';
    case Pending = 'pending';
    /** Đã giữ tiền, chưa thu — chỉ cổng `CapturesLater`. */
    case Authorized = 'authorized';

    /** Trạng thái làm khoản thanh toán có tiền (thu hoặc giữ) — phải khớp số tiền. */
    public function settles(): bool
    {
        return $this === self::Paid || $this === self::Authorized;
    }
}
