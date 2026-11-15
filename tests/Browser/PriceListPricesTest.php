<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Trang làm việc của bảng giá (thao tác gọn, đợt 1) trên trình duyệt thật.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage']), 'staff');
    $this->style = T::product();
    [$this->s, $this->m] = P::variants($this->style);
    P::priceList(['code' => 'base'], [$this->s->id => [299_000], $this->m->id => [299_000]]);
    $this->list = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);
});

it('bảng giá trống mở được, gợi ý thêm sản phẩm', function () {
    visit("/admin/pricing/price-lists/{$this->list->id}/prices")->assertSee('Bảng giá chưa có sản phẩm')->assertNoJavaScriptErrors();
});

it('thêm sản phẩm → giảm 30% → xem trước → áp dụng: giá vào bảng, trang hiện −30%', function () {
    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices");

    $page->click('[data-add-products]')
        ->type('input[aria-label="Tìm sản phẩm"]', $this->style->style_code)
        ->click("[data-candidate=\"{$this->style->style_code}\"]")
        ->click('[data-continue]')
        ->type('input[aria-label="Giảm %"]', '30')
        ->click('[data-preview]')->assertSee('2 SKU đổi giá')
        ->click('[data-apply]')->assertSee('Đã cập nhật giá 2 SKU.')->assertSee('−30%');

    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount')->all())->toBe([209_000, 209_000]);
});

it('đến từ danh sách sản phẩm ("Đưa vào bảng giá…"): hộp thoại cách tính mở sẵn, giảm 20% → áp dụng', function () {
    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices?add={$this->style->id}");

    $page->assertSee('Đưa 1 sản phẩm vào')
        ->type('input[aria-label="Giảm %"]', '20')
        ->click('[data-preview]')->assertSee('2 SKU đổi giá')
        ->click('[data-apply]')->assertSee('Đã cập nhật giá 2 SKU.');

    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount')->all())->toBe([239_000, 239_000]);
});

it('bảng giá niêm yết: chỉ xem, chỉ đường sửa giá ở sản phẩm', function () {
    $base = PriceList::query()->where('code', 'base')->sole();

    visit("/admin/pricing/price-lists/{$base->id}/prices")->assertSee('chỉ xem ở đây')->assertSee('Sửa giá ở danh sách sản phẩm')->assertDontSee('Thêm sản phẩm')->assertNoJavaScriptErrors();
});

it('nhập giá từ file: chọn tệp, nhập, bảng hiện giá mới; dòng lỗi hiện trong hộp thoại', function () {
    $path = tempnam(sys_get_temp_dir(), 'gia').'.csv';
    file_put_contents($path, "SKU;Giá\n{$this->s->sku};150.000\nKHONG-CO;1000\n");

    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices");
    $page->click('[data-import]')->attach('input[type="file"]', $path)->click('[data-import-submit]')
        ->assertSee('Dòng 3: không có SKU "KHONG-CO".');

    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 150_000]);
});
