<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Plugin\Warehouse\WarehouseServiceProvider;

require_once __DIR__.'/../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

it('chọn nguồn quản lý tồn có sẵn và lưu mức ưu tiên riêng trong form kho', function () {
    T::seed(function (): void {
        app(PluginManager::class)->install(WarehouseServiceProvider::ID);
        app(PluginManager::class)->enable(WarehouseServiceProvider::ID);
        app()->register(WarehouseServiceProvider::class);
    });
    $warehouse = I::location(['code' => 'WH-TEST', 'stock_authority' => 'partner_erp']);
    $this->actingAs(T::staff(['admin.access', 'warehouse.locations.manage']), 'staff');

    visit("/admin/plugins/vani-warehouse/locations/{$warehouse->id}/edit")
        ->assertSeeIn('#warehouse-authority', 'partner_erp')
        ->click('#warehouse-authority')
        ->click('[role="option"] >> text=VaniShop')
        ->click('#warehouse-priority')
        ->click('[role="option"] >> text=Mức ưu tiên riêng')
        ->type('#warehouse-custom-priority', '7')
        ->press('Lưu')
        ->assertSee('Đã lưu')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('locations', ['id' => $warehouse->id, 'stock_authority' => 'vanishop', 'priority' => 7]);
});
