<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

/**
 * Mức giảm tối đa so với giá niêm yết (giá sale của bảng giá và khuyến mãi — VN: 50%, NĐ 81/2018). Cấu hình `core`
 * theo phần trăm (Admin → Cài đặt → Bán hàng & thuế); mặc định theo `vanishop.promotion.max_discount_bp` (.env).
 */
final class DiscountCap
{
    public const SETTING = 'pricing.max_discount_percent';

    /** Phần trăm cấu hình → basis point (100% = 10 000 = không giới hạn). */
    public static function basisPoints(mixed $percent): int
    {
        return max(1, min(100, (int) $percent)) * 100;
    }
}
