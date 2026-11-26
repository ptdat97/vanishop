<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/PricingTestHelpers.php';

/*
| Stabilization A5: lịch sử giá theo SKU — mọi lần đổi giá (ai, lúc nào, bảng giá nào, cũ → mới), tìm theo SKU/mã sản phẩm.
*/

beforeEach(function () {
    $this->staff = T::staff(['admin.access', 'catalog.view', 'pricing.view', 'pricing.manage']);
    $this->staff->update(['name' => 'Lan CSKH']);
    $this->actingAs($this->staff, 'staff');
    $this->dress = T::product(null, ['product_code' => 'DAM01', 'name' => 'Đầm lụa']);
    $this->shirt = T::product(null, ['product_code' => 'AO01', 'name' => 'Áo thun']);
    [$this->s] = P::variants($this->dress, ['S']);
    [$this->a] = P::variants($this->shirt, ['M']);
    P::priceList(['code' => 'base'], []);
    $this->sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10, 'name' => 'Sale 11/11'], []);
});

it('ghi lại mỗi lần đổi giá; trang lịch sử hiện SKU, sản phẩm, bảng giá, cũ → mới, giá gạch, người đổi; mới nhất trước', function () {
    $this->post('/admin/pricing/base-prices/bulk', ['ids' => [$this->dress->id, $this->shirt->id], 'mode' => 'set', 'value' => 300_000])->assertSessionHasNoErrors();
    $this->post('/admin/pricing/base-prices/bulk', ['ids' => [$this->dress->id], 'mode' => 'percent_down', 'value' => 10, 'strike' => 1])->assertSessionHasNoErrors();

    $this->get('/admin/pricing/history')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Pricing::History/Index')
        ->where('rows.total', 3)
        ->where('rows.data.0.sku', $this->s->sku)
        ->where('rows.data.0.product', fn (string $product): bool => str_starts_with($product, 'Đầm lụa'))
        ->where('rows.data.0.price_list', 'Bảng giá')
        ->where('rows.data.0.old', '300.000 ₫')
        ->where('rows.data.0.new', '270.000 ₫')
        ->where('rows.data.0.old_compare', null)
        ->where('rows.data.0.new_compare', '300.000 ₫')
        ->where('rows.data.0.actor', 'Lan CSKH')
        ->where('rows.data.2.old', null));
});

it('tìm theo SKU/mã sản phẩm và lọc theo bảng giá', function () {
    $this->post('/admin/pricing/base-prices/bulk', ['ids' => [$this->dress->id, $this->shirt->id], 'mode' => 'set', 'value' => 300_000]);
    $this->put("/admin/pricing/price-lists/{$this->sale->id}/prices", ['prices' => [['variant_id' => $this->a->id, 'amount' => 199_000, 'compare_at_amount' => null]]])->assertSessionHasNoErrors();

    $total = fn (array $query): int => $this->get('/admin/pricing/history?'.http_build_query($query))->viewData('page')['props']['rows']['total'];
    expect($total(['q' => 'AO01']))->toBe(2)
        ->and($total(['q' => $this->s->sku]))->toBe(1)
        ->and($total(['price_list' => $this->sale->id]))->toBe(1)
        ->and($total(['q' => 'khong-co-gi']))->toBe(0);
});

it('cần quyền xem giá; trang bảng giá có link Lịch sử giá', function () {
    $this->get('/admin/pricing/price-lists')->assertInertia(fn (Assert $page) => $page->where('historyUrl', url('/admin/pricing/history')));
    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $this->get('/admin/pricing/history')->assertForbidden();
});
