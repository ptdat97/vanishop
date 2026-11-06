<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Inventory\Contracts\LocationRegistry;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Warehouse\Persistence\Transfer;
use Plugin\Warehouse\WarehouseServiceProvider;

require_once __DIR__.'/../../../../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(WarehouseServiceProvider::ID);
        app(PluginManager::class)->enable(WarehouseServiceProvider::ID);
    });
    app()->register(WarehouseServiceProvider::class);
    $this->data = fn ($location) => app(LocationRegistry::class)->find($location->id);
    $this->brand = Brand::factory()->create();
    $this->style = T::product($this->brand->id, ['style_code' => 'LM-DR01']);
    [$this->s, $this->m] = P::variants($this->style);
    $this->hn = I::location(['code' => 'WH-HN', 'priority' => 10]);
    $this->hcm = I::location(['code' => 'WH-HCM', 'priority' => 5]);
    $this->url = '/admin/plugins/vani-warehouse/transfers';
    $this->manager = fn () => $this->actingAs(T::staff(['admin.access', 'inventory.view', 'warehouse.transfer']), 'staff');
});

it('tạo phiếu theo SKU rồi gửi và nhận từ Admin', function () {
    I::stock($this->hn, $this->s->id, 10);
    ($this->manager)();

    $this->post($this->url, [
        'from_location_id' => $this->hn->id,
        'to_location_id' => $this->hcm->id,
        'lines' => [['sku' => $this->s->sku, 'quantity' => 4]],
    ])->assertSessionHasNoErrors();

    $transfer = Transfer::query()->sole();
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
