<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A5: "Đổi giá" ở danh sách sản phẩm có bước xem trước (thao tác hàng loạt có `preview_url`) trên trình duyệt thật.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.view', 'pricing.manage']), 'staff');
    $this->product = T::product(null, ['product_code' => 'DAM01']);
    [$s, $m] = P::variants($this->product);
    $this->base = P::priceList(['code' => 'base'], [$s->id => [300_000], $m->id => [200_000]]);
});

it('chọn sản phẩm → Đổi giá (giảm 10%) → xem trước số SKU và khoảng giá → xác nhận thì mới ghi', function () {
    $page = visit('/admin/catalog/products');

    $page->click('input[aria-label="Chọn DAM01"]')
        ->click('[data-extension-action="price"]')
        ->click('[role="alertdialog"] button[type="submit"]')
        ->assertSee('1 sản phẩm, 2 SKU: giá niêm yết 200.000 ₫ – 300.000 ₫ → 180.000 ₫ – 270.000 ₫.');
    expect(Price::query()->where('price_list_id', $this->base->id)->orderBy('amount')->pluck('amount')->all())->toEqual([200_000, 300_000]);

    $page->click('[role="alertdialog"] button[type="submit"]')->assertSee('Đã đổi giá 2 biến thể của 1 sản phẩm');
    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->base->id)->orderBy('amount')->pluck('amount')->all())->toEqual([180_000, 270_000]);
});

it('chọn bảng giá và xem trước rồi áp dụng ngay tại danh sách sản phẩm', function () {
    $sale = P::priceList(['code' => 'flash-sale', 'type' => 'sale'], []);
    T::seed(fn () => $sale->update(['name' => 'Sale cuối tuần']));
    $page = visit('/admin/catalog/products?q=DAM01');

    $page->click('input[aria-label="Chọn DAM01"]')
        ->click('[data-extension-action="add_to_price_list"]')
        ->assertVisible('[role="alertdialog"] button[type="submit"]:disabled')
        ->click('[role="alertdialog"] [aria-label="Bảng giá"]')->click('[role="option"] >> text=Sale cuối tuần')
        ->fill('[role="alertdialog"] input[required]', '10')
        ->click('[role="alertdialog"] button[type="submit"]')
        ->assertSee('Bảng Sale cuối tuần · 2/2 SKU đổi giá, bỏ qua 0 SKU.');
    expect($sale->prices()->count())->toBe(0);

    $page->click('[role="alertdialog"] button[type="submit"]')
        ->assertSee('Đã cập nhật giá 2 SKU.')
        ->assertPathIs('/admin/catalog/products')
        ->assertNoJavaScriptErrors();
    expect($sale->prices()->orderBy('amount')->pluck('amount')->all())->toBe([180_000, 270_000])
        ->and($this->base->prices()->orderBy('amount')->pluck('amount')->all())->toBe([200_000, 300_000]);
});
