<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

function stockOnHand(Location $location, int $variantId): int
{
    return (int) DB::table('stock_levels')->where('location_id', $location->id)->where('variant_id', $variantId)->value('on_hand');
}

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
        ->where('variants.0.sellable', 8)
        ->where('canAdjust', false)
        ->where('urls.locations', null));

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

it('lưới tồn mới: tìm theo tên/SKU, lọc hết hàng / sắp hết, phân trang; form sản phẩm có link Tồn kho', function () {
    config(['vanishop.inventory.low_stock_threshold' => 3]);
    I::stock($this->hn, $this->s->id, 2);
    $other = T::product($this->brand->id, ['style_code' => 'LM-TS02', 'name' => 'Áo thun']);
    [$otherS] = P::variants($other, ['S']);
    I::stock($this->hn, $otherS->id, 20);
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'catalog.view', 'catalog.manage']), 'staff');
    $skus = fn (string $query) => collect($this->get("{$this->stockUrl}{$query}")->assertOk()->viewData('page')['props']['variants'])->pluck('sku')->all();

    expect($skus(''))->toHaveCount(3)
        ->and($skus('?q=thun'))->toBe([$otherS->sku])
        ->and($skus('?q='.$this->m->sku))->toBe([$this->m->sku])
        ->and($skus('?filter=out'))->toBe([$this->m->sku])
        ->and($skus('?filter=low'))->toBe([$this->s->sku])
        ->and($skus('?style=LM-DR01'))->toHaveCount(2);

    $this->get("/admin/catalog/products/{$this->style->id}/edit")->assertInertia(fn (Assert $page) => $page->where('stockUrl', fn (string $url) => str_ends_with($url, '/admin/inventory/stock?style=LM-DR01')));
});

it('lưu nhiều ô một lần như kiểm kê; ô có tồn đã đổi sau khi mở trang không bị ghi đè; kho đồng bộ bị chặn', function () {
    I::stock($this->hn, $this->s->id, 5);
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');

    $this->post("{$this->stockUrl}/batch", ['changes' => [
        ['location_id' => $this->hn->id, 'variant_id' => $this->s->id, 'on_hand' => 12, 'expected' => 5],
        ['location_id' => $this->hn->id, 'variant_id' => $this->m->id, 'on_hand' => 4, 'expected' => 3], // đã đổi (đang 0)
    ]])->assertSessionHasErrors('changes')->assertSessionHas('success', 'Đã cập nhật 1 ô tồn.');

    expect(stockOnHand($this->hn, $this->s->id))->toBe(12)
        ->and(stockOnHand($this->hn, $this->m->id))->toBe(0)
        ->and(StockMovement::query()->where('variant_id', $this->s->id)->latest('id')->value('reason'))->toBe('Cập nhật tồn');

    $this->post("{$this->stockUrl}/batch", ['changes' => [['location_id' => $this->erp->id, 'variant_id' => $this->s->id, 'on_hand' => 1, 'expected' => 0]]])
        ->assertSessionHasErrors('location_id');
    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');
    $this->post("{$this->stockUrl}/batch", ['changes' => [['location_id' => $this->hn->id, 'variant_id' => $this->s->id, 'on_hand' => 1, 'expected' => 12]]])->assertForbidden();
});

it('nhập CSV: xem trước (không ghi), áp dụng như kiểm kê, bỏ dòng có tồn đổi sau xem trước; xuất CSV nhập lại được', function () {
    I::stock($this->hn, $this->s->id, 5);
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $csv = "\xEF\xBB\xBFsku;on_hand;location\n{$this->s->sku};8;wh-hn\n{$this->m->sku};3;\nKHONG-CO;1;\n{$this->s->sku};9;ST-ERP\n{$this->m->sku};x;\n";
    $file = UploadedFile::fake()->createWithContent('kiem-ke.csv', $csv);

    $response = $this->post("{$this->stockUrl}/import", ['file' => $file, 'location_id' => $this->hn->id])->assertOk();
    $preview = $response->viewData('page')['props']['preview'];
    expect(array_column($preview['lines'], 'status'))->toBe(['change', 'change', 'unknown_sku', 'external', 'invalid'])
        ->and(stockOnHand($this->hn, $this->s->id))->toBe(5);

    I::stock($this->hn, $this->m->id, 1); // đổi sau khi xem trước → dòng này bị bỏ qua
    $this->post("{$this->stockUrl}/import/{$preview['token']}")->assertRedirect()->assertSessionHasErrors('changes');
    expect(stockOnHand($this->hn, $this->s->id))->toBe(8)
        ->and(stockOnHand($this->hn, $this->m->id))->toBe(1)
        ->and(StockMovement::query()->where('variant_id', $this->s->id)->latest('id')->value('reason'))->toBe('Nhập CSV kiem-ke.csv');
    $this->post("{$this->stockUrl}/import/{$preview['token']}")->assertSessionHasErrors('token'); // dùng một lần

    $export = $this->get("{$this->stockUrl}/export?location=WH-HN")->assertOk()->streamedContent();
    expect($export)->toStartWith("\xEF\xBB\xBFsku,location,on_hand")->toContain("{$this->s->sku},WH-HN,8,");
    $roundTrip = UploadedFile::fake()->createWithContent('ton-kho.csv', $export);
    $again = $this->post("{$this->stockUrl}/import", ['file' => $roundTrip])->assertOk()->viewData('page')['props']['preview'];
    expect($again['token'])->toBeNull()->and($again['summary'])->toBe(['same' => 2]);
});

it('cửa hàng chưa có kho: trang tồn cảnh báo, nút tạo Kho chính; lệnh ensure-location chạy lại an toàn', function () {
    T::seed(fn () => Location::query()->delete());
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'inventory.view', 'inventory.locations.manage'])->create(), 'staff');

    $this->get($this->stockUrl)->assertInertia(fn (Assert $page) => $page->where('warnings.no_location', true)->where('urls.default_location', fn ($url) => $url !== null));
    $this->post("{$this->stockUrl}/default-location")->assertSessionHas('success');
    $location = T::seed(fn () => Location::query()->sole());
    expect($location->only(['code', 'name', 'ships_online_orders', 'accepts_returns', 'stock_authority', 'status']))
        ->toBe(['code' => 'KHO-CHINH', 'name' => 'Kho chính', 'ships_online_orders' => true, 'accepts_returns' => true, 'stock_authority' => 'vanishop', 'status' => 'active']);

    $this->artisan('vani:inventory:ensure-location')->assertSuccessful();
    expect(T::seed(fn () => Location::query()->count()))->toBe(1);
    $this->get($this->stockUrl)->assertInertia(fn (Assert $page) => $page->where('warnings.no_location', false)->where('warnings.no_online_location', false));
});
