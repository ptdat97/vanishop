<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Persistence\Models\Price;
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
