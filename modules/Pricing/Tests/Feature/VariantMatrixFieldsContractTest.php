<?php

use Modules\Catalog\Testing\VariantFieldProviderContract;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Inventory\Application\VariantStockFields;
use Modules\Pricing\Application\VariantPriceFields;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Cột Core của ma trận biến thể chạy cùng contract test mà plugin dùng (variant-matrix §5).
*/

$product = function (): array {
    ['s' => $s, 'm' => $m] = C::store();

    return [$s->style_id, [$s->id, $m->id]];
};

VariantFieldProviderContract::define('pricing (giá niêm yết)', fn () => app(VariantPriceFields::class), $product);
VariantFieldProviderContract::define('inventory (tồn kho duy nhất)', fn () => app(VariantStockFields::class), $product);
