<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Persistence\Models\StockTransfer;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/InventoryTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->style = T::product($this->brand->id, ['style_code' => 'LM-DR01']);
    [$this->s, $this->m] = P::variants($this->style);
    $this->hn = I::location(['code' => 'WH-HN', 'priority' => 10]);
    $this->hcm = I::location(['code' => 'WH-HCM', 'priority' => 5]);
    $this->url = '/admin/inventory/transfers';
    $this->manager = fn () => $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.transfer']), 'staff');
});

it('tạo phiếu theo SKU rồi gửi và nhận từ Admin', function () {
    I::stock($this->hn, $this->s->id, 10);
    ($this->manager)();

    $this->post($this->url, [
        'from_location_id' => $this->hn->id,
        'to_location_id' => $this->hcm->id,
        'lines' => [['sku' => $this->s->sku, 'quantity' => 4]],
    ])->assertSessionHasNoErrors();

    $transfer = StockTransfer::query()->sole();
    $this->post("{$this->url}/{$transfer->id}/ship")->assertSessionHasNoErrors();
    $this->post("{$this->url}/{$transfer->id}/receive")->assertSessionHasNoErrors();

    expect($transfer->fresh()->status->value)->toBe('received')
        ->and((int) DB::table('stock_levels')->where('location_id', $this->hcm->id)->where('variant_id', $this->s->id)->value('on_hand'))->toBe(4);
});

it('SKU không tồn tại bị từ chối', function () {
    ($this->manager)();

    $this->post($this->url, [
        'from_location_id' => $this->hn->id,
        'to_location_id' => $this->hcm->id,
        'lines' => [['sku' => 'NOPE', 'quantity' => 1]],
    ])->assertSessionHasErrors('lines.0.sku');
});

it('màn hình chuyển kho cần quyền xem; thao tác cần quyền chuyển kho', function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');
    $this->get($this->url)->assertOk();
    $this->post($this->url, [
        'from_location_id' => $this->hn->id,
        'to_location_id' => $this->hcm->id,
        'lines' => [['sku' => $this->s->sku, 'quantity' => 1]],
    ])->assertForbidden();
});
