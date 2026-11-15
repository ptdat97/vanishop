<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage']), 'staff');
    $this->list = P::priceList(['code' => 'sale-1111', 'type' => 'sale'], []);
});

it('trang nhập giá mở được (chưa chọn mã sản phẩm)', function () {
    visit("/admin/pricing/price-lists/{$this->list->id}/prices")->assertSee('Nhập giá: Bảng giá')->assertNoJavaScriptErrors();
});

it('tải biến thể theo mã sản phẩm: hiện các biến thể và giá đang có', function () {
    $style = T::product();
    [$s, $m] = P::variants($style);
    T::seed(fn () => $this->list->prices()->create(['variant_id' => $s->id, 'amount' => 199_000, 'compare_at_amount' => 299_000]));

    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices?style={$style->style_code}")
        ->assertSee($s->sku)->assertSee($m->sku)->assertSee('199.000');

    $page->assertNoJavaScriptErrors();
});
