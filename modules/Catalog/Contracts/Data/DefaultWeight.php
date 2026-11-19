<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

use Modules\Tenancy\Contracts\Settings;

/**
 * Khối lượng mặc định của một sản phẩm (gram) khi SKU chưa nhập khối lượng — Admin → Cài đặt → Kho & giao hàng (mặc
 * định VANI_FULFILLMENT_DEFAULT_ITEM_GRAM). Áp vào `SellableVariant::$weightGram` (phí giao lúc thanh toán, file gửi hãng).
 */
final class DefaultWeight
{
    public const SETTING = 'catalog.default_weight_gram';

    public static function grams(Settings $settings): int
    {
        return max(1, (int) $settings->get('core', self::SETTING));
    }
}
