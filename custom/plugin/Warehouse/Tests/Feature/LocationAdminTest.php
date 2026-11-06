<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Warehouse\WarehouseServiceProvider;

require_once __DIR__.'/../../../../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(WarehouseServiceProvider::ID);
        app(PluginManager::class)->enable(WarehouseServiceProvider::ID);
    });
    app()->register(WarehouseServiceProvider::class);
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->style = T::product($this->brand->id, ['style_code' => 'LM-DR01']);
    [$this->s, $this->m] = P::variants($this->style);
    $this->hn = I::location(['code' => 'WH-HN', 'priority' => 10]);
    $this->erp = I::location(['code' => 'ST-ERP', 'stock_authority' => 'erp']);
    $this->stockUrl = '/admin/inventory/stock';
});

function locationPayload(array $overrides = []): array
{
    return array_replace([
        'code' => 'WH-HCM', 'name' => 'Kho Hồ Chí Minh', 'type' => 'warehouse',
        'ships_online_orders' => true, 'allows_pickup' => false, 'accepts_returns' => true,
        'stock_authority' => 'vanishop', 'priority' => 5, 'status' => 'active',
    ], $overrides);
}

it('tạo và sửa location, có optimistic lock và audit', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'warehouse.locations.manage'])->create(), 'staff');

    $this->get('/admin/plugins/vani-warehouse/locations')->assertInertia(fn (Assert $page) => $page->component('Warehouse::Locations/Index')->has('locations', 2));
    $this->post('/admin/plugins/vani-warehouse/locations', locationPayload())->assertSessionHasNoErrors();

    $location = T::seed(fn () => Location::query()->where('code', 'WH-HCM')->sole());
    $this->get("/admin/plugins/vani-warehouse/locations/{$location->id}/edit")->assertInertia(fn (Assert $page) => $page->component('Warehouse::Locations/Form')
        ->where('location.code', 'WH-HCM'));

    $this->put("/admin/plugins/vani-warehouse/locations/{$location->id}", locationPayload(['name' => 'Kho Thủ Đức', 'lock_version' => 0]))->assertSessionHasNoErrors();
    $this->put("/admin/plugins/vani-warehouse/locations/{$location->id}", locationPayload(['lock_version' => 0]))->assertSessionHasErrors('lock_version');

    expect($location->fresh()->name)->toBe('Kho Thủ Đức')
        ->and(AuditLog::query()->where('action', 'like', 'inventory.location.%')->count())->toBe(2);
    $this->post('/admin/plugins/vani-warehouse/locations', locationPayload())->assertSessionHasErrors('code');
});

it('thiếu quyền quản lý kho thì không vào được màn hình location', function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');

    $this->get('/admin/plugins/vani-warehouse/locations')->assertForbidden();
    $this->post('/admin/plugins/vani-warehouse/locations', locationPayload())->assertForbidden();
});
