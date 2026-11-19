<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts\Data;

/**
 * Khoá cấu hình giỏ hàng (namespace `core`, Admin → Cài đặt → Bán hàng & thuế); mặc định theo `vanishop.cart.*` (.env).
 */
final class CartSettings
{
    /** Số lượng tối đa của một dòng giỏ. */
    public const MAX_LINE_QUANTITY = 'cart.max_line_quantity';

    /** Số dòng tối đa của một giỏ. */
    public const MAX_LINES = 'cart.max_lines';

    /** Giỏ có liên hệ không hoạt động quá số phút này → sự kiện giỏ bị bỏ quên (plugin nhắc giỏ). */
    public const ABANDONED_AFTER_MINUTES = 'cart.abandoned_after_minutes';

    /** Giỏ không hoạt động quá số ngày này bị xoá. */
    public const TTL_DAYS = 'cart.ttl_days';
}
