<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';
require_once __DIR__.'/../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A6: phiếu nhập nhanh trên trình duyệt thật — quét SKU (Enter) cộng dồn, "SKU 5", lưu và nhập kho.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $this->location = I::location(['code' => 'KHO-CHINH']);
    [$this->s, $this->m] = P::variants(T::product(null, ['style_code' => 'DAM01']));
});

it('quét SKU hai lần → số lượng 2; "SKU 5" → 5; Lưu và nhập kho → tồn tăng đúng', function () {
    $page = visit('/admin/inventory/receipts/create');

    $page->type('[data-scan]', $this->s->sku)->keys('[data-scan]', 'Enter')->assertSee($this->s->sku)
        ->type('[data-scan]', $this->s->sku)->keys('[data-scan]', 'Enter')
        ->type('[data-scan]', "{$this->m->sku} 5")->keys('[data-scan]', 'Enter')
        ->assertSee('2 SKU · 7 món')
        ->click('[data-receive]')
        ->click('[role="alertdialog"] button[type="submit"]')
        ->assertSee('Đã nhập kho phiếu');

    $page->assertNoJavaScriptErrors();
    expect(DB::table('stock_levels')->where('location_id', $this->location->id)->orderBy('variant_id')->pluck('on_hand', 'variant_id')->all())
        ->toEqual([$this->s->id => 2, $this->m->id => 5]);
});
