<?php

use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

/*
| Form sản phẩm trên trình duyệt thật. Hồi quy 2026-11-13: trang "Thêm sản phẩm" treo — template truyền
| `product?.variants ?? []` (mảng mới mỗi lần render) cho ma trận biến thể → `summary` tính lại → trang render lại, lặp vô hạn.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.manage', 'inventory.adjust']), 'staff');
});

it('trang Thêm sản phẩm mở được, chọn "Có biến thể" mở được popup biến thể, lưu tạo sản phẩm', function () {
    $page = visit('/admin/catalog/products/create')->assertSee('Thêm sản phẩm')->assertSee('Loại sản phẩm');

    $page->click('[data-product-kind] >> text=Có biến thể')->assertSee('Chưa có biến thể');
    $page->click('[data-open-variants]')->assertSee('Biến thể — sản phẩm mới')->keys('[role="dialog"]', ['Escape']);
    $page->type('form input >> nth=0', 'Áo sơ mi lụa')
        ->type('form > div:last-child input >> nth=0', 'LM-NEW-01')
        ->type('form > div:last-child input >> nth=1', 'ao-so-mi-lua')
        ->press('Lưu')
        ->assertSee('Sửa: LM-NEW-01')
        ->assertNoJavaScriptErrors();

    expect(T::seed(fn () => Style::query()->where('style_code', 'LM-NEW-01')->exists()))->toBeTrue();
});

it('mặc định là sản phẩm đơn: dòng SKU không màu / size, nút Thêm ảnh ở cột Ảnh mở Thư viện ảnh', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'media.view', 'media.manage']), 'staff');

    visit('/admin/catalog/products/create')
        ->assertPresent('[data-single-variant]')
        ->assertNotPresent('[data-single-variant] [aria-label="Màu"]')
        ->assertDontSee('Ảnh sản phẩm')
        ->click('[data-single-variant] >> text=Thêm ảnh')
        ->assertSee('Chọn ảnh từ thư viện')
        ->assertSee('Tải ảnh lên')
        ->assertNoJavaScriptErrors();
});

it('sản phẩm đơn: nhập giá → Lưu tạo sản phẩm kèm một SKU theo mã sản phẩm; trang sửa vẫn là sản phẩm đơn', function () {
    $page = visit('/admin/catalog/products/create');
    $page->type('form input >> nth=0', 'Áo thun trơn')
        ->type('form > div:last-child input >> nth=0', 'LM-ONE-01')
        ->type('form > div:last-child input >> nth=1', 'ao-thun-tron')
        ->type('[data-single-variant] input[aria-label="Giá bán (niêm yết)"]', '250000')
        ->press('Lưu')
        ->assertSee('Sửa: LM-ONE-01')
        ->assertPresent('[data-single-variant]')
        ->assertNotPresent('[data-product-kind]')
        ->assertNoJavaScriptErrors();

    expect(T::seed(fn () => Variant::query()->where('sku', 'LM-ONE-01')->exists()))->toBeTrue();
});

it('Thiết lập biến thể khi thêm mới: chọn màu → có ô Ảnh theo màu ngay trong popup (lưu cùng nút Lưu)', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'media.view', 'media.manage']), 'staff');
    T::seed(fn () => OptionValue::factory()->color()->create(['code' => 'IVR']));

    $page = visit('/admin/catalog/products/create')->click('[data-product-kind] >> text=Có biến thể')->click('[data-open-variants]')->assertSee('Biến thể — sản phẩm mới');
    $page->click('[aria-label="Thêm giá trị Màu"]')->click('[role="option"] >> nth=0');

    $page->assertSee('Ảnh theo màu')
        ->assertPresent('[data-value-images] [data-media-field]')
        ->assertNoJavaScriptErrors();
});

it('sản phẩm đơn đã lưu: Chuyển sang có biến thể → chọn màu, SKU hiện có thành một ô, Lưu', function () {
    $color = T::seed(fn () => OptionValue::factory()->color()->create(['code' => 'IVR']));
    $style = T::product(T::seed(fn () => Brand::factory()->create())->id, ['style_code' => 'LM-CVT-01']);
    $variantId = T::seed(fn () => app(ProductOptions::class)->createVariant($style, [], ['sku' => 'LM-CVT-01'])->id);

    $page = visit("/admin/catalog/products/{$style->id}/edit")->assertPresent('[data-single-variant]');
    $page->click('[data-convert-to-variants]')->assertSee('Biến thể —')
        ->click('[aria-label="Thêm trục"]')->click('[role="option"] >> text=Màu')
        ->click('[aria-label="Thêm giá trị Màu"]')->click('[role="option"] >> text=IVR')
        ->assertPresent('[data-adopt-single]')
        ->click('[data-save-variants]')
        ->assertNotPresent('[data-single-variant]')
        ->assertNoJavaScriptErrors();

    expect(T::seed(fn () => Variant::query()->with('optionValues')->find($variantId)->optionValues->pluck('id')->all()))->toBe([$color->id]);
});
