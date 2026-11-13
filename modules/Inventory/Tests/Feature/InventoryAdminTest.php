<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Application\LowStockThreshold;
use Modules\Inventory\Application\StockGrid;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockMovement;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Tenancy\Contracts\Settings;

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

it('lưới tồn: variant × location, ATS = tồn − giữ − an toàn', function () {
    I::stock($this->hn, $this->s->id, 10, 2);
    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');

    $this->get("{$this->stockUrl}?style=LM-DR01&per_page=50")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Inventory::Stock/Index')
        ->where('pagination.per_page', 50)
        ->has('locations', 2)
        ->where('locations.0.code', 'WH-HN')
        ->where('locations.1.external', true)
        ->has('products', 1)
        ->has('products.0.variants', 2)
        ->where('products.0.variants.0.levels.0.available', 8)
        ->where('products.0.variants.1.levels.0.on_hand', 0)
        ->where('products.0.variants.0.sellable', 8)
        ->where('products.0.sellable', 8)
        ->where('canAdjust', false)
        ->where('urls.locations', null));
    $this->get("{$this->stockUrl}?per_page=101")->assertSessionHasErrors('per_page');

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

it('lưới tồn mới: tìm theo tên/SKU, lọc hết hàng / sắp hết, phân trang; sản phẩm mở lưới tồn theo mã', function () {
    config(['vanishop.inventory.low_stock_threshold' => 3]);
    I::stock($this->hn, $this->s->id, 2);
    $other = T::product($this->brand->id, ['style_code' => 'LM-TS02', 'name' => 'Áo thun']);
    [$otherS] = P::variants($other, ['S']);
    I::stock($this->hn, $otherS->id, 20);
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'catalog.view', 'catalog.manage']), 'staff');
    $skus = fn (string $query) => collect($this->get("{$this->stockUrl}{$query}")->assertOk()->viewData('page')['props']['products'])->flatMap(fn (array $product) => $product['variants'])->pluck('sku')->all();

    expect($skus(''))->toHaveCount(3)
        ->and($skus('?q=thun'))->toBe([$otherS->sku])
        ->and($skus('?q='.$this->m->sku))->toBe([$this->m->sku])
        ->and($skus('?filter=out'))->toBe([$this->m->sku])
        ->and($skus('?filter=low'))->toBe([$this->s->sku])
        ->and($skus('?style=LM-DR01'))->toHaveCount(2)
        ->and($skus('?q=LM-DR01&opt[size]='.T::seed(fn () => $this->m->valueOf('size')?->code)))->toBe([$this->m->sku]);
    // Lọc theo loại tuỳ chọn (ADR-045): lựa chọn chỉ gồm giá trị có trong kết quả tìm; nhãn SKU là tổ hợp giá trị.
    $props = $this->get("{$this->stockUrl}?q=LM-DR01")->viewData('page')['props'];
    expect(collect($props['optionFilters'])->pluck('code')->all())->toBe(['color', 'size'])
        ->and(collect($props['optionFilters'])->firstWhere('code', 'size')['values'])->toHaveCount(2)
        ->and($props['products'][0]['variants'][0]['options_label'])->toContain(' / ');

    $this->get("/admin/catalog/products/{$this->style->id}/edit")->assertInertia(fn (Assert $page) => $page->where('stockUrl', fn (string $url) => str_ends_with($url, '/admin/inventory/stock?style=LM-DR01')));
    $this->get('/admin/catalog/products')->assertInertia(fn (Assert $page) => $page->where(
        "extensions.urls.{$this->style->id}.stock",
        route('admin.inventory.stock.index', ['style' => $this->style->style_code]),
    ));
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

it('sửa hàng loạt tại một kho: SKU đã chọn, cả sản phẩm hoặc mọi kết quả lọc; trừ quá tồn thì bỏ qua SKU đó', function () {
    I::stock($this->hn, $this->s->id, 5);
    $other = T::product($this->brand->id, ['style_code' => 'LM-TS02', 'name' => 'Áo thun']);
    [$otherS, $otherM] = P::variants($other);
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $bulk = fn (array $data) => $this->post("{$this->stockUrl}/bulk", ['location_id' => $this->hn->id, ...$data]);

    // Đặt tồn = 10 cho hai SKU đã chọn.
    $bulk(['action' => 'set', 'quantity' => 10, 'variant_ids' => [$this->s->id, $this->m->id]])->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Tại WH-HN: đã đổi 2 SKU, 0 SKU giữ nguyên.');
    expect(stockOnHand($this->hn, $this->s->id))->toBe(10)->and(stockOnHand($this->hn, $this->m->id))->toBe(10);

    // Chọn mọi kết quả lọc theo tên "thun" (cả trang khác): cộng 3, cần lý do.
    $bulk(['action' => 'adjust', 'quantity' => 3, 'all' => true, 'criteria' => ['q' => 'thun']])->assertSessionHasErrors('reason');
    $bulk(['action' => 'adjust', 'quantity' => 3, 'reason' => 'Nhập hàng', 'all' => true, 'criteria' => ['q' => 'thun']])->assertSessionHasNoErrors();
    expect(stockOnHand($this->hn, $otherS->id))->toBe(3)->and(stockOnHand($this->hn, $otherM->id))->toBe(3)
        ->and(stockOnHand($this->hn, $this->s->id))->toBe(10);

    // Trừ 5 cho cả 4 SKU: SKU chỉ có 3 bị bỏ qua, phần còn lại vẫn áp.
    $bulk(['action' => 'adjust', 'quantity' => -5, 'reason' => 'Hàng lỗi', 'variant_ids' => [$this->s->id, $this->m->id, $otherS->id, $otherM->id]])
        ->assertSessionHasErrors('changes');
    expect(stockOnHand($this->hn, $this->s->id))->toBe(5)->and(stockOnHand($this->hn, $otherS->id))->toBe(3);

    // Tồn an toàn (được cả kho đồng bộ), số âm bị chặn; kho đồng bộ không đặt tồn được.
    $bulk(['action' => 'safety', 'quantity' => 1, 'variant_ids' => [$this->s->id]])->assertSessionHasNoErrors();
    $bulk(['action' => 'set', 'quantity' => -1, 'variant_ids' => [$this->s->id]])->assertSessionHasErrors('quantity');
    $this->post("{$this->stockUrl}/bulk", ['location_id' => $this->erp->id, 'action' => 'set', 'quantity' => 1, 'variant_ids' => [$this->s->id]])->assertSessionHasErrors('location_id');
    expect(T::seed(fn () => DB::table('stock_levels')->where('location_id', $this->hn->id)->where('variant_id', $this->s->id)->value('safety_stock')))->toBe(1)
        ->and(AuditLog::query()->where('action', 'inventory.stock.bulk')->count())->toBe(4);

    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');
    $bulk(['action' => 'set', 'quantity' => 1, 'variant_ids' => [$this->s->id]])->assertForbidden();
});

it('ngưỡng "sắp hết hàng" đổi ở Admin → Cấu hình: lưới tồn và Tổng quan theo ngưỡng mới; chỉ nhận 0–1000', function () {
    I::stock($this->hn, $this->s->id, 5);
    I::stock($this->hn, $this->m->id, 9);
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'settings.manage']), 'staff');
    $low = fn () => collect($this->get("{$this->stockUrl}?filter=low")->assertOk()->viewData('page')['props']['products'])->flatMap(fn (array $product) => $product['variants'])->pluck('sku')->all();
    expect($low())->toBe([]);

    T::seed(fn () => app(Settings::class)->set('core', LowStockThreshold::SETTING, 5));
    expect($low())->toBe([$this->s->sku])
        ->and($this->get($this->stockUrl)->viewData('page')['props']['threshold'])->toBe(5)
        ->and(app(StockGrid::class)->attentionCounts()['low'])->toBe(1);

    $definition = collect(app(Settings::class)->definitions('core'))->firstWhere('key', LowStockThreshold::SETTING);
    expect($definition->type)->toBe('int')->and($definition->rules)->toBe(['min:0', 'max:1000']);
});

it('danh sách sản phẩm: cửa hàng một kho sửa tồn ngay trong ô (từng màu/size); nhiều kho hoặc thiếu quyền thì chỉ có link sang lưới tồn', function () {
    // Cửa hàng SME: chỉ còn Kho chính.
    T::seed(fn () => $this->erp->delete());
    I::stock($this->hn, $this->s->id, 4);
    $styleId = $this->s->style_id;
    $stockEdit = fn (): mixed => $this->get('/admin/catalog/products')->assertOk()->viewData('page')['props']['extensions']['editValues'][$styleId]['stock'] ?? null;

    // Chỉ xem tồn: không có dữ liệu sửa.
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'inventory.view']), 'staff');
    expect($stockEdit())->toBeNull();

    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'inventory.view', 'inventory.adjust']), 'staff');
    $edit = $stockEdit();
    expect($edit['location_id'])->toBe($this->hn->id)
        ->and(collect($edit['rows'])->firstWhere('variant_id', $this->s->id)['on_hand'])->toBe(4)
        ->and(array_column($edit['rows'], 'variant_id'))->toContain($this->m->id);

    // Lưu đúng như giao diện gửi: chỉ ô đã đổi, kèm số tồn lúc mở trang.
    $this->post($edit['url'], ['changes' => [['location_id' => $edit['location_id'], 'variant_id' => $this->s->id, 'on_hand' => 9, 'expected' => 4]], 'reason' => 'Sửa nhanh ở danh sách sản phẩm'])
        ->assertSessionHasNoErrors();
    expect(collect($stockEdit()['rows'])->firstWhere('variant_id', $this->s->id)['on_hand'])->toBe(9)
        ->and(DB::table('stock_movements')->where('variant_id', $this->s->id)->latest('id')->value('reason'))->toBe('Sửa nhanh ở danh sách sản phẩm');

    // Có kho thứ hai (vani.warehouse): sửa ở lưới tồn theo từng kho.
    I::location(['code' => 'KHO-2']);
    expect($stockEdit())->toBeNull();
});

it('cửa hàng lớn (6.000 biến thể): lưới tồn, Tổng quan, "chọn mọi kết quả" và file xuất thấy đủ — không cắt âm thầm; số truy vấn không tăng theo số biến thể', function () {
    // Dựng nhanh bằng insert thô: 300 mẫu × 4 màu × 5 size, mẫu đầu tiên còn hàng ở kho giao online.
    $now = now();
    $type = fn (string $code): int => (int) DB::table('option_types')->where('code', $code)->value('id');
    [$colorType, $sizeType] = [$type('color'), $type('size')];
    $colors = collect(range(1, 4))->map(fn (int $i): int => DB::table('option_values')->insertGetId(['option_type_id' => $colorType, 'code' => "BIG-C{$i}", 'family' => 'black', 'position' => $i, 'created_at' => $now, 'updated_at' => $now]))->all();
    $sizes = collect(range(1, 5))->map(fn (int $i): int => DB::table('option_values')->insertGetId(['option_type_id' => $sizeType, 'group' => 'big', 'code' => "B{$i}", 'position' => $i, 'created_at' => $now, 'updated_at' => $now]))->all();
    $variants = [];
    for ($style = 1; $style <= 300; $style++) {
        $styleId = DB::table('styles')->insertGetId(['brand_id' => $this->brand->id, 'style_code' => sprintf('BIG-%04d', $style), 'slug' => "big-{$style}", 'status' => 'active', 'search_text' => "mau lon {$style}", 'created_at' => $now, 'updated_at' => $now]);
        DB::table('style_translations')->insert(['style_id' => $styleId, 'locale' => 'vi', 'name' => sprintf('Mẫu lớn %04d', $style)]);
        DB::table('style_options')->insert([['style_id' => $styleId, 'option_type_id' => $colorType, 'position' => 0], ['style_id' => $styleId, 'option_type_id' => $sizeType, 'position' => 1]]);
        DB::table('style_option_values')->insert(array_map(fn (int $valueId): array => ['style_id' => $styleId, 'option_value_id' => $valueId, 'position' => $valueId], [...$colors, ...$sizes]));
        foreach ($colors as $colorId) {
            foreach ($sizes as $sizeId) {
                $variants[] = ['style_id' => $styleId, 'option_key' => "{$colorId}-{$sizeId}", 'sku' => "BIG-{$style}-{$colorId}-{$sizeId}", 'status' => 'active', 'created_at' => $now, 'updated_at' => $now];
            }
        }
    }
    foreach (array_chunk($variants, 500) as $chunk) {
        DB::table('variants')->insert($chunk);
    }
    $links = [];
    foreach (DB::table('variants')->where('sku', 'like', 'BIG-%')->get(['id', 'option_key']) as $row) {
        [$colorId, $sizeId] = array_map('intval', explode('-', $row->option_key));
        $links[] = ['variant_id' => $row->id, 'option_type_id' => $colorType, 'option_value_id' => $colorId];
        $links[] = ['variant_id' => $row->id, 'option_type_id' => $sizeType, 'option_value_id' => $sizeId];
    }
    foreach (array_chunk($links, 500) as $chunk) {
        DB::table('variant_option_values')->insert($chunk);
    }
    $firstBig = (int) DB::table('variants')->where('sku', 'like', 'BIG-1-%')->min('id');
    I::stock($this->hn, $firstBig, 50);
    $total = 6000 + 2; // + 2 biến thể của LM-DR01 (hết hàng)
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust', 'catalog.view']), 'staff');

    DB::enableQueryLog();
    $page = $this->get("{$this->stockUrl}?page=16")->assertOk()->viewData('page')['props'];
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($page['pagination'])->toMatchArray(['total' => 301, 'variants' => $total])
        ->and(collect($page['products'])->pluck('style_code')->all())->toHaveCount(1) // trang cuối: 301 mẫu, 20 mẫu/trang
        ->and($queries)->toBeLessThan(40)
        ->and(app(StockGrid::class)->attentionCounts()['out'])->toBe($total - 1)
        ->and(app(StockGrid::class)->variantIds(['filter' => 'out']))->toHaveCount($total - 1)
        ->and(app(StockGrid::class)->page([], 151, 20)['total_variants'])->toBe($total);

    $exported = $this->get("{$this->stockUrl}/export")->assertOk()->streamedContent();
    expect(substr_count($exported, "\n"))->toBeGreaterThanOrEqual(1 + $total * 2); // tiêu đề + mỗi biến thể × 2 kho

    // Sửa hàng loạt chạy đồng bộ: vượt mức → báo thu hẹp bộ lọc, không ghi gì.
    $this->post("{$this->stockUrl}/bulk", ['location_id' => $this->hn->id, 'action' => 'safety', 'quantity' => 1, 'all' => true, 'criteria' => ['filter' => 'out']])
        ->assertSessionHasErrors(['changes']);
    expect(DB::table('stock_levels')->where('safety_stock', 1)->count())->toBe(0);
});
