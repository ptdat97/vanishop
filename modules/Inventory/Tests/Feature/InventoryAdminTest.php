<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockMovement;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/InventoryTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->web = Channel::factory()->forBrand($this->brand, 'vani.test', '/lumiere')->create();
    $this->style = T::product($this->brand->id, ['style_code' => 'LM-DR01']);
    [$this->s, $this->m] = P::variants($this->style);
    $this->hn = I::location($this->brand, [$this->web->id], ['code' => 'WH-HN', 'priority' => 10]);
    $this->erp = I::location($this->brand, [$this->web->id], ['code' => 'ST-ERP', 'stock_authority' => 'erp']);
    $this->foreign = I::location($this->other, [], ['code' => 'WH-UX']);
    $this->stockUrl = '/admin/inventory/lumiere/stock';
});

function locationPayload(Brand $brand, array $overrides = []): array
{
    return array_replace([
        'code' => 'WH-HCM', 'name' => 'Kho Hồ Chí Minh', 'type' => 'warehouse', 'legal_entity_id' => $brand->legal_entity_id,
        'ships_online_orders' => true, 'allows_pickup' => false, 'accepts_returns' => true,
        'stock_authority' => 'vanishop', 'priority' => 5, 'status' => 'active', 'brand_ids' => [$brand->id], 'channel_ids' => [],
    ], $overrides);
}

it('Owner tạo và sửa location, gán brand/kênh, có optimistic lock và audit', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'inventory.locations.manage'])->create(), 'staff');

    $this->get('/admin/inventory/locations')->assertInertia(fn (Assert $page) => $page->component('Inventory::Locations/Index')->has('locations', 3));
    $this->post('/admin/inventory/locations', locationPayload($this->brand, ['channel_ids' => [$this->web->id]]))->assertSessionHasNoErrors();

    $location = T::seed(fn () => Location::query()->where('code', 'WH-HCM')->sole());
    $this->get("/admin/inventory/locations/{$location->id}/edit")->assertInertia(fn (Assert $page) => $page->component('Inventory::Locations/Form')
        ->where('location.brand_ids', [$this->brand->id])->where('location.channel_ids', [$this->web->id]));

    $this->put("/admin/inventory/locations/{$location->id}", locationPayload($this->brand, ['name' => 'Kho Thủ Đức', 'lock_version' => 0]))->assertSessionHasNoErrors();
    $this->put("/admin/inventory/locations/{$location->id}", locationPayload($this->brand, ['lock_version' => 0]))->assertSessionHasErrors('lock_version');

    expect($location->fresh()->name)->toBe('Kho Thủ Đức')
        ->and(AuditLog::query()->where('action', 'like', 'inventory.location.%')->count())->toBe(2);
    $this->post('/admin/inventory/locations', locationPayload($this->brand))->assertSessionHasErrors('code');
});

it('nhân viên brand không quản lý được location cấp Owner', function () {
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'inventory.view', 'inventory.adjust', 'inventory.locations.manage']), 'staff');

    $this->get('/admin/inventory/locations')->assertForbidden();
    $this->post('/admin/inventory/locations', locationPayload($this->brand))->assertForbidden();
});

it('lưới tồn: variant × location của brand, ATS = tồn − giữ − an toàn', function () {
    I::stock($this->hn, $this->s->id, 10, 2);
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'inventory.view']), 'staff');

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
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $change = fn (array $data) => $this->post($this->stockUrl, ['location_id' => $this->hn->id, 'variant_id' => $this->s->id, ...$data]);

    $change(['action' => 'adjust', 'quantity' => 12, 'reason' => 'Nhập hàng'])->assertSessionHasNoErrors();
    $change(['action' => 'count', 'quantity' => 9, 'reason' => 'Kiểm kê tháng 10'])->assertSessionHasNoErrors();
    $change(['action' => 'safety', 'quantity' => 1])->assertSessionHasNoErrors();
    $change(['action' => 'adjust', 'quantity' => -50, 'reason' => 'Sai'])->assertSessionHasErrors('quantity');
    $change(['action' => 'adjust', 'quantity' => 1])->assertSessionHasErrors('reason');

    expect(StockMovement::query()->where('variant_id', $this->s->id)->orderBy('id')->pluck('on_hand_after')->all())->toBe([12, 9, 9]);

    $this->get("/admin/inventory/lumiere/movements?variant={$this->s->id}")->assertInertia(fn (Assert $page) => $page->component('Inventory::Stock/Movements')
        ->has('movements', 3)
        ->where('movements.0.type', 'safety_stock')
        ->where('movements.2.reason', 'Nhập hàng'));
});

it('không điều chỉnh tay location do hệ thống ngoài quản lý', function () {
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');

    $this->post($this->stockUrl, ['location_id' => $this->erp->id, 'variant_id' => $this->s->id, 'action' => 'adjust', 'quantity' => 5, 'reason' => 'x'])
        ->assertSessionHasErrors('location_id');
});

it('cô lập brand: không chạm được location/variant của brand khác', function () {
    $urbanxStyle = T::product($this->other->id, ['style_code' => 'UX-TS01']);
    [$foreignVariant] = P::variants($urbanxStyle, ['S']);
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');

    $this->post($this->stockUrl, ['location_id' => $this->foreign->id, 'variant_id' => $this->s->id, 'action' => 'adjust', 'quantity' => 1, 'reason' => 'x'])->assertNotFound();
    $this->post($this->stockUrl, ['location_id' => $this->hn->id, 'variant_id' => $foreignVariant->id, 'action' => 'adjust', 'quantity' => 1, 'reason' => 'x'])->assertNotFound();
    $this->get("/admin/inventory/lumiere/movements?variant={$foreignVariant->id}")->assertNotFound();
    $this->get('/admin/inventory/urbanx/stock')->assertNotFound();
    $this->get("{$this->stockUrl}?style=UX-TS01")->assertInertia(fn (Assert $page) => $page->has('variants', 0));
});
