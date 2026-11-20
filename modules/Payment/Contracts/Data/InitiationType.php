<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Việc khách cần làm tiếp sau khi khởi tạo thanh toán.
 */
#[PluginApi]
enum InitiationType: string
{
    /** Không cần làm gì (COD). */
    case None = 'none';
    /** Chuyển sang trang cổng. */
    case Redirect = 'redirect';
    /** Quét mã QR. */
    case Qr = 'qr';
    /** Làm theo hướng dẫn (chuyển khoản thủ công). */
    case Instructions = 'instructions';
}
