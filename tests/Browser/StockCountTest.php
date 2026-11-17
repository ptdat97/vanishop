<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';
require_once __DIR__.'/../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A7: kiểm kê trên trình duyệt thật — quét nhanh liên tiếp không mất mã (hàng đợi), áp dụng ghi phần chênh.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $this->location = I::location(['code' => 'KHO-CHINH']);
    [$this->s] = P::variants(T::product(null, ['style_code' => 'DAM01']), ['S']);
    I::stock($this->location, $this->s->id, 5);
});

it('mở phiên → quét 3 lần liên tiếp → số đếm 3, lệch −2 → áp dụng → tồn 3', function () {
    $page = visit('/admin/inventory/counts');
    $page->click('[data-start-count] button[type="submit"]')->assertSee('Kiểm kê KK');

    foreach (range(1, 3) as $ignored) {
        $page->type('[data-scan]', $this->s->sku)->keys('[data-scan]', 'Enter');
    }
    $page->assertSee('1 SKU lệch (+0 / -2)')
        ->click('[data-apply]')
        ->click('[role="alertdialog"] button[type="submit"]')
        ->assertSee('1 SKU đổi tồn');

    $page->assertNoJavaScriptErrors();
    expect((int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('on_hand'))->toBe(3);
});
