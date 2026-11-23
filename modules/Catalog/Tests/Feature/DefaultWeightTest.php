<?php

use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Tenancy\Contracts\Settings;

/*
| Khối lượng mặc định (Admin → Cài đặt → Kho & giao hàng): SKU chưa nhập khối lượng dùng giá trị mặc định khi bán — phí giao
| lúc thanh toán và file gửi hãng đọc khối lượng từ sellableVariants.
*/

it('SKU chưa nhập khối lượng → khối lượng mặc định (theo cài đặt, đọc lúc dùng); SKU đã nhập giữ khối lượng của mình', function () {
    [$withoutWeight, $zeroWeight, $withWeight] = T::seed(fn (): array => [
        app(ProductOptions::class)->createVariant(T::product(null, ['status' => 'active']), [], ['sku' => 'NO-WEIGHT', 'weight_gram' => null]),
        app(ProductOptions::class)->createVariant(T::product(null, ['status' => 'active']), [], ['sku' => 'ZERO-WEIGHT', 'weight_gram' => 0]),
        app(ProductOptions::class)->createVariant(T::product(null, ['status' => 'active']), [], ['sku' => 'HAS-WEIGHT', 'weight_gram' => 450]),
    ]);
    $weights = fn (): array => array_map(fn ($variant): ?int => $variant->weightGram, app(CatalogReader::class)->sellableVariants([$withoutWeight->id, $zeroWeight->id, $withWeight->id], 'vi', time()));

    expect($weights())->toBe([$withoutWeight->id => 300, $zeroWeight->id => 300, $withWeight->id => 450]);

    app(Settings::class)->set('core', 'catalog.default_weight_gram', 800);
    expect($weights())->toBe([$withoutWeight->id => 800, $zeroWeight->id => 800, $withWeight->id => 450])
        ->and($withoutWeight->fresh()->weight_gram)->toBeNull()
        ->and($zeroWeight->fresh()->weight_gram)->toBe(0);
});
