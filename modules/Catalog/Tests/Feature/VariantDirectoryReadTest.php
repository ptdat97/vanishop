<?php

use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

/*
| VariantDirectory: find / ofProducts / findBySkus đọc thẳng bằng query builder (nhanh cho danh sách lớn) phải trả đúng như
| đường qua model (ofProductCode): tên theo locale có fallback, tuỳ chọn theo thứ tự trục, SKU theo vị trí giá trị của sản phẩm.
*/

beforeEach(function () {
    T::seed(function () {
        $this->product = T::product(null, ['product_code' => 'DR-01', 'name' => 'Đầm lụa']);
        $this->product->syncTranslations(['vi' => ['name' => 'Đầm lụa'], 'en' => ['name' => 'Silk dress']]);
        $color = OptionValue::factory()->color()->create(['code' => 'IVR']);
        $color->syncTranslations(['vi' => ['name' => 'Trắng ngà']]);
        $sizes = [
            OptionValue::factory()->size()->create(['code' => 'L']),
            OptionValue::factory()->size()->create(['code' => 'S']),
        ];
        // Trục: Size trước, Màu sau; vị trí giá trị trong sản phẩm: S trước L (khác thứ tự tạo).
        app(ProductOptions::class)->syncAxes($this->product, [
            ['option_type_id' => $sizes[0]->option_type_id, 'value_ids' => [$sizes[1]->id, $sizes[0]->id]],
            ['option_type_id' => $color->option_type_id, 'value_ids' => [$color->id]],
        ]);
        foreach ($sizes as $size) {
            app(ProductOptions::class)->createVariant($this->product, [$size->id, $color->id], ['sku' => "DR-01-{$size->code}"]);
        }
        $this->single = T::product(null, ['product_code' => 'TS-01', 'name' => 'Áo thun']);
        app(ProductOptions::class)->createVariant($this->single, [], ['sku' => 'TS-01']);
    });
});

it('find, ofProducts, findBySkus khớp đường đọc qua model, theo cả hai ngôn ngữ', function (string $locale, string $name, string $colorType) {
    app()->setLocale($locale);
    $directory = app(VariantDirectory::class);
    $viaModels = $directory->ofProductCode('DR-01');

    expect(array_map(fn ($variant) => $variant->sku, $viaModels))->toBe(['DR-01-S', 'DR-01-L'])
        ->and($directory->ofProducts([$this->product->id, $this->single->id]))->toEqual([$this->product->id => $viaModels, $this->single->id => $directory->ofProductCode('TS-01')])
        ->and(array_values($directory->find(array_map(fn ($variant) => $variant->id, $viaModels))))->toEqualCanonicalizing($viaModels)
        ->and($directory->findBySkus(['DR-01-L'])['DR-01-L'])->toEqual($viaModels[1])
        ->and($viaModels[0]->productName)->toBe($name)
        ->and($viaModels[0]->options)->toBe([
            ['type' => 'size', 'name' => 'Size', 'code' => 'S', 'value' => 'S'],
            ['type' => 'color', 'name' => $colorType, 'code' => 'IVR', 'value' => 'Trắng ngà'],
        ])
        ->and($directory->findBySkus(['TS-01'])['TS-01']->options)->toBe([]);
})->with([
    'tiếng Việt' => ['vi', 'Đầm lụa', 'Màu'],
    'tiếng Anh (giá trị màu chưa dịch → tiếng Việt)' => ['en', 'Silk dress', 'Colour'],
]);
