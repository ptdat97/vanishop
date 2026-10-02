<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockMovement;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/InventoryTestHelpers.php';

beforeEach(function () {
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
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'inventory.locations.manage'])->create(), 'staff');

    $this->get('/admin/inventory/locations')->assertInertia(fn (Assert $page) => $page->component('Inventory::Locations/Index')->has('locations', 2));
    $this->post('/admin/inventory/locations', locationPayload())->assertSessionHasNoErrors();

    $location = T::seed(fn () => Location::query()->where('code', 'WH-HCM')->sole());
    $this->get("/admin/inventory/locations/{$location->id}/edit")->assertInertia(fn (Assert $page) => $page->component('Inventory::Locations/Form')
        ->where('location.code', 'WH-HCM'));

    $this->put("/admin/inventory/locations/{$location->id}", locationPayload(['name' => 'Kho Thủ Đức', 'lock_version' => 0]))->assertSessionHasNoErrors();
    $this->put("/admin/inventory/locations/{$location->id}", locationPayload(['lock_version' => 0]))->assertSessionHasErrors('lock_version');

    expect($location->fresh()->name)->toBe('Kho Thủ Đức')
        ->and(AuditLog::query()->where('action', 'like', 'inventory.location.%')->count())->toBe(2);
    $this->post('/admin/inventory/locations', locationPayload())->assertSessionHasErrors('code');
});

it('thiếu quyền quản lý kho thì không vào được màn hình location', function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');

    $this->get('/admin/inventory/locations')->assertForbidden();
    $this->post('/admin/inventory/locations', locationPayload())->assertForbidden();
});

it('lưới tồn: variant × location, ATS = tồn − giữ − an toàn', function () {
    I::stock($this->hn, $this->s->id, 10, 2);
    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');

    $this->get("{$this->stockUrl}?style=LM-DR01")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Inventory::Stock/Index')
        ->has('locations', 2)
        ->where('locations.0.code', 'WH-HN')
        ->where('locations.1.external', true)
        ->has('variants', 2)
        ->where('variants.0.levels.0.available', 8)
        ->where('variants.1.levels.0.on_hand', 0)
        ->where('canAdjust', false)
        ->where('locationsUrl', null));

    $this->post($this->stockUrl, ['location_id' => $this->hn->id, 'variant_id' => $this->s->id, 'action' => 'adjust', 'quantity' => 1, 'reason' => 'x'])->assertForbidden();
});

it('điều chỉnh, kiểm kê, tồn an toàn — ghi sổ và xem lịch sử', function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $change = fn (array $data) => $this->post($this->stockUrl, ['location_id' => $this->hn->id, 'variant_id' => $this->s->id, ...$data]);

    $change(['action' => 'adjust', 'quantity' => 12, 'reason' => 'Nhập hàng'])->assertSessionHasNoErrors();
    $change(['action' => 'count', 'quantity' => 9, 'reason' => 'Kiểm kê tháng 10'])->assertSessionHasNoErrors();
    $change(['action' => 'safety', 'quantity' => 1])->assertSessionHasNoErrors();
    $change(['action' => 'adjust', 'quantity' => -50, 'reason' => 'Sai'])->assertSessionHasErrors('quantity');
    $change(['action' => 'adjust', 'quantity' => 1])->assertSessionHasErrors('reason');

    expect(StockMovement::query()->where('variant_id', $this->s->id)->orderBy('id')->pluck('on_hand_after')->all())->toBe([12, 9, 9]);

    $this->get("/admin/inventory/movements?variant={$this->s->id}")->assertInertia(fn (Assert $page) => $page->component('Inventory::Stock/Movements')
        ->has('movements', 3)
        ->where('movements.0.type', 'safety_stock')
        ->where('movements.2.reason', 'Nhập hàng'));
});

it('không điều chỉnh tay location do hệ thống ngoài quản lý', function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');

    $this->post($this->stockUrl, ['location_id' => $this->erp->id, 'variant_id' => $this->s->id, 'action' => 'adjust', 'quantity' => 5, 'reason' => 'x'])
        ->assertSessionHasErrors('location_id');
});

it('variant/location không tồn tại trả 404', function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');

    $this->post($this->stockUrl, ['location_id' => 999_999, 'variant_id' => $this->s->id, 'action' => 'adjust', 'quantity' => 1, 'reason' => 'x'])->assertNotFound();
    $this->get('/admin/inventory/movements?variant=999999')->assertNotFound();
});
