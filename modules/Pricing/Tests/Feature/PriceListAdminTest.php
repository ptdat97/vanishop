<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Pricing\Application\PriceListFile;
use Modules\Pricing\Application\PriceListWorkspace;
use Modules\Pricing\Events\PriceChanged;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Support\SimpleXlsx;

require_once __DIR__.'/PricingTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->style = T::product($this->brand->id, ['style_code' => 'LM-SH01']);
    [$this->s, $this->m] = P::variants($this->style);
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage']), 'staff');
    $this->base = '/admin/pricing/price-lists';
});

function listPayload(array $overrides = []): array
{
    return array_replace(['code' => 'base', 'name' => 'Giá niêm yết', 'type' => 'base', 'priority' => 0, 'status' => 'active'], $overrides);
}

it('tạo bảng giá', function () {
    $this->post($this->base, listPayload())->assertSessionHasNoErrors();

    $list = T::seed(fn () => PriceList::query()->sole());
    $this->get("{$this->base}/{$list->id}/edit")->assertInertia(fn (Assert $page) => $page->component('Pricing::PriceLists/Form')->where('priceList.code', 'base'));
});

it('khung giờ phải hợp lệ; mã duy nhất', function () {
    $this->post($this->base, listPayload(['starts_at' => '2026-11-12 00:00', 'ends_at' => '2026-11-11 00:00']))->assertSessionHasErrors('ends_at');
    $this->post($this->base, listPayload());
    $this->post($this->base, listPayload())->assertSessionHasErrors('code');
});

it('khung giờ nhập và hiển thị theo múi giờ cửa hàng (vanishop.locale.timezone), lưu UTC', function () {
    config(['vanishop.locale.timezone' => 'Asia/Ho_Chi_Minh']);
    $this->post($this->base, listPayload(['starts_at' => '2026-11-11T08:00']))->assertSessionHasNoErrors();

    $list = T::seed(fn () => PriceList::query()->sole());
    expect($list->starts_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-11 01:00');
    $this->get("{$this->base}/{$list->id}/edit")->assertInertia(fn (Assert $page) => $page->where('priceList.starts_at', '2026-11-11T08:00'));

    config(['vanishop.locale.timezone' => 'Asia/Tokyo', 'vanishop.locale.formats.datetime' => 'Y/m/d H:i']);
    $this->get("{$this->base}/{$list->id}/edit")->assertInertia(fn (Assert $page) => $page->where('priceList.starts_at', '2026-11-11T10:00'));
    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->where('priceLists.0.starts_at', '2026/11/11 10:00'));
});

it('nhập giá hàng loạt, ghi lịch sử giá, audit và phát PriceChanged', function () {
    Event::fake([PriceChanged::class]);
    $list = P::priceList(['code' => 'sale-1', 'type' => 'sale'], []);

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [
        ['variant_id' => $this->s->id, 'amount' => 590000, 'compare_at_amount' => null],
        ['variant_id' => $this->m->id, 'amount' => 590000, 'compare_at_amount' => 690000],
    ]])->assertSessionHasNoErrors();

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [
        ['variant_id' => $this->s->id, 'amount' => 550000, 'compare_at_amount' => null],
        ['variant_id' => $this->m->id, 'amount' => null, 'compare_at_amount' => null],
    ]])->assertSessionHasNoErrors();

    expect(Price::query()->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 550000])
        ->and(PriceHistory::query()->where('variant_id', $this->s->id)->orderBy('id')->get(['old_amount', 'new_amount'])->toArray())
        ->toBe([['old_amount' => null, 'new_amount' => 590000], ['old_amount' => 590000, 'new_amount' => 550000]])
        ->and(PriceHistory::query()->where('variant_id', $this->m->id)->latest('id')->value('new_amount'))->toBeNull()
        ->and(AuditLog::query()->where('action', 'pricing.prices.updated')->count())->toBe(2);
    Event::assertDispatchedTimes(PriceChanged::class, 2);
});

it('không ghi lịch sử khi giá không đổi', function () {
    $list = P::priceList(['code' => 'sale-1', 'type' => 'sale'], [$this->s->id => [590_000]]);

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 590000, 'compare_at_amount' => null]]])->assertSessionHasNoErrors();

    expect(PriceHistory::query()->count())->toBe(0);
});

it('từ chối giá gốc không lớn hơn giá bán, số âm và biến thể không tồn tại', function () {
    $list = P::priceList(['code' => 'sale-1', 'type' => 'sale'], []);

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 500000, 'compare_at_amount' => 500000]]])->assertSessionHasErrors('prices.0.compare_at_amount');
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => -1]]])->assertSessionHasErrors('prices.0.amount');
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => 999_999, 'amount' => 1000]]])->assertSessionHasErrors('prices.0.variant_id');
});

it('trang làm việc: mọi SKU đang có trong bảng giá, tìm theo tên/mã/SKU, giá niêm yết, % giảm, cảnh báo; tóm tắt cả bảng', function () {
    $other = T::product($this->brand->id, ['style_code' => 'LM-DR02']);
    [$dress] = P::variants($other, ['S']);
    P::priceList(['code' => 'base'], [$this->s->id => [500_000], $this->m->id => [500_000], $dress->id => [800_000]]);
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10, 'starts_at' => now()->addDay()], [$this->s->id => [350_000], $this->m->id => [0], $dress->id => [800_000]]);

    $this->get("{$this->base}/{$sale->id}/prices")->assertInertia(fn (Assert $page) => $page->component('Pricing::PriceLists/Prices')
        ->where('priceList.state', 'scheduled')
        ->has('rows', 3)
        ->where('summary', ['products' => 2, 'variants' => 3, 'warnings' => ['zero' => 1, 'over_cap' => 1, 'not_lower' => 1]])
        ->where('rows', fn ($rows) => collect($rows)->keyBy('variant_id')->pipe(fn ($rows) => $rows[$this->s->id]['list_amount'] === 500_000 && $rows[$this->s->id]['percent'] === 30 && $rows[$this->s->id]['warnings'] === []
            && $rows[$this->m->id]['warnings'] === ['zero', 'over_cap'] && $rows[$dress->id]['warnings'] === ['not_lower'])));
    $this->get("{$this->base}/{$sale->id}/prices?q=LM-DR02")->assertInertia(fn (Assert $page) => $page->has('rows', 1)->where('rows.0.variant_id', $dress->id));
    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->where('priceLists', fn ($lists) => collect($lists)->firstWhere('id', $sale->id)['state'] === 'scheduled'));
});

it('thêm sản phẩm: tìm theo tên/mã, xem trước giảm 30% (làm tròn 1.000đ), áp dụng ghi lịch sử giá; SKU chưa có giá niêm yết bị bỏ qua', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [499_000]]);
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);
    $rule = ['scope' => 'styles', 'style_ids' => [$this->style->id], 'mode' => 'percent', 'value' => 30, 'round_to' => 1000];

    $this->getJson("{$this->base}/{$sale->id}/candidates?q=lm-sh")->assertOk()
        ->assertJsonPath('data.0.id', $this->style->id)->assertJsonPath('data.0.code', 'LM-SH01')->assertJsonPath('data.0.meta', 'niêm yết 499.000 ₫')
        ->assertJsonPath('data.0.variants.0.id', $this->s->id)->assertJsonPath('data.0.variants.0.sku', $this->s->sku)
        ->assertJsonPath('data.0.variants.0.status', 'active')->assertJsonPath('data.0.variants.0.meta', 'niêm yết 499.000 ₫')
        ->assertJsonPath('data.0.variants.1.meta', null);
    $this->postJson("{$this->base}/{$sale->id}/bulk/preview", $rule)->assertOk()
        ->assertJsonPath('data.summary', ['total' => 2, 'changed' => 1, 'skipped' => 1, 'warnings' => ['no_base' => 1]])
        ->assertJsonPath('data.rows.0.new_amount', 349_000)->assertJsonPath('data.rows.0.percent', 30);
    $this->post("{$this->base}/{$sale->id}/bulk", $rule)->assertSessionHas('success', 'Đã cập nhật giá 1 SKU. Bỏ qua 1 SKU chưa có giá niêm yết.');

    expect(Price::query()->where('price_list_id', $sale->id)->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 349_000])
        ->and(PriceHistory::query()->where('price_list_id', $sale->id)->count())->toBe(1);
});

it('áp giá cho SKU đã chọn hoặc toàn bộ bảng: đồng giá 0đ được (chỉ cảnh báo), giảm số tiền không âm; giá gạch còn lớn hơn được giữ; gỡ SKU', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [300_000], $this->m->id => [300_000]]);
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], [$this->s->id => [250_000, 400_000], $this->m->id => [250_000]]);

    $this->postJson("{$this->base}/{$sale->id}/bulk/preview", ['scope' => 'variants', 'variant_ids' => [$this->m->id], 'mode' => 'fixed', 'value' => 0, 'round_to' => 0])
        ->assertOk()->assertJsonPath('data.summary.warnings', ['zero' => 1, 'over_cap' => 1]);
    $this->post("{$this->base}/{$sale->id}/bulk", ['scope' => 'variants', 'variant_ids' => [$this->m->id], 'mode' => 'fixed', 'value' => 0, 'round_to' => 0])->assertSessionHasNoErrors();
    $this->post("{$this->base}/{$sale->id}/bulk", ['scope' => 'all', 'q' => 'LM-SH01-S', 'mode' => 'amount', 'value' => 500_000, 'round_to' => 1000])->assertSessionHasNoErrors();

    expect(Price::query()->where('price_list_id', $sale->id)->get()->keyBy('variant_id')->map(fn (Price $price): array => [$price->amount, $price->compare_at_amount])->all())
        ->toBe([$this->s->id => [0, 400_000], $this->m->id => [0, null]]);

    $this->post("{$this->base}/{$sale->id}/remove", ['variant_ids' => [$this->s->id, $this->m->id]])->assertSessionHas('success', 'Đã gỡ 2 SKU khỏi bảng giá.');
    expect(Price::query()->where('price_list_id', $sale->id)->count())->toBe(0);
});

/**
 * Chèn nhanh $count SKU (bản sao cột của một SKU thật, khác SKU/khoá tuỳ chọn) kèm giá niêm yết và giá trong bảng sale.
 *
 * @return list<int>
 */
function manySkusInSale(object $test, int $count, int $saleId, int $baseId): array
{
    $template = (array) DB::table('variants')->where('id', $test->s->id)->first();
    unset($template['id']);
    foreach (array_chunk(range(1, $count), 500) as $chunk) {
        DB::table('variants')->insert(array_map(fn (int $i): array => ['sku' => "BULK-{$i}", 'option_key' => "bulk-{$i}", 'barcode' => null] + $template, $chunk));
    }
    $ids = DB::table('variants')->where('sku', 'like', 'BULK-%')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    foreach ([$baseId => 200_000, $saleId => 180_000] as $listId => $amount) {
        foreach (array_chunk($ids, 500) as $chunk) {
            DB::table('prices')->insert(array_map(fn (int $id): array => ['price_list_id' => $listId, 'variant_id' => $id, 'amount' => $amount, 'min_qty' => 1, 'created_at' => now(), 'updated_at' => now()], $chunk));
        }
    }

    return $ids;
}

it('áp giá cả bảng: đổi đủ mọi SKU (trước đây cắt âm thầm về 2.000 SKU đầu)', function () {
    $base = P::priceList(['code' => 'base'], []);
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);
    manySkusInSale($this, 2_100, $sale->id, $base->id);
    $rule = ['scope' => 'all', 'mode' => 'percent', 'value' => 50, 'round_to' => 1000];

    $this->postJson("{$this->base}/{$sale->id}/bulk/preview", $rule)->assertOk()->assertJsonPath('data.summary.total', 2_100);
    $this->post("{$this->base}/{$sale->id}/bulk", $rule)->assertSessionHasNoErrors()->assertSessionHas('success', 'Đã cập nhật giá 2100 SKU.');

    expect(Price::query()->where('price_list_id', $sale->id)->where('amount', 100_000)->count())->toBe(2_100);
});

it('áp giá: phạm vi vượt mức mỗi lần thì báo số SKU để thu hẹp, không đổi giá nào', function () {
    $base = P::priceList(['code' => 'base'], []);
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);
    manySkusInSale($this, PriceListWorkspace::MAX_BULK + 1, $sale->id, $base->id);
    $rule = ['scope' => 'all', 'mode' => 'percent', 'value' => 50, 'round_to' => 1000];

    $this->postJson("{$this->base}/{$sale->id}/bulk/preview", $rule)->assertUnprocessable()
        ->assertJsonPath('errors.scope.0', 'Phạm vi có 5.001 SKU, vượt mức 5.000 SKU mỗi lần áp giá. Thu hẹp ô tìm (theo mã, tên sản phẩm) rồi áp từng phần.');
    $this->post("{$this->base}/{$sale->id}/bulk", $rule)->assertSessionHasErrors('scope');
    $this->post("{$this->base}/{$sale->id}/bulk", [...$rule, 'q' => 'BULK-1'])->assertSessionHasNoErrors();

    expect(Price::query()->where('price_list_id', $sale->id)->where('amount', 100_000)->count())->toBe(1_111); // BULK-1, BULK-10…19, BULK-100…199, BULK-1000…1999
});

it('áp giá hàng loạt: quyền sửa giá và dữ liệu hợp lệ', function () {
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale'], []);

    $this->post("{$this->base}/{$sale->id}/bulk", ['scope' => 'styles', 'style_ids' => [$this->style->id], 'mode' => 'percent', 'value' => 120, 'round_to' => 1000])->assertSessionHasErrors('value');
    $this->post("{$this->base}/{$sale->id}/bulk", ['scope' => 'variants', 'mode' => 'percent', 'value' => 10, 'round_to' => 500])->assertSessionHasErrors(['variant_ids', 'round_to']);
    $this->actingAs(T::staff(['admin.access', 'pricing.view']), 'staff');
    $this->post("{$this->base}/{$sale->id}/bulk", ['scope' => 'styles', 'style_ids' => [$this->style->id], 'mode' => 'percent', 'value' => 10, 'round_to' => 1000])->assertForbidden();
    $this->getJson("{$this->base}/{$sale->id}/candidates?q=LM")->assertForbidden();
    $this->post("{$this->base}/{$sale->id}/remove", ['variant_ids' => [$this->s->id]])->assertForbidden();
});

it('quyền chỉ xem không sửa được giá', function () {
    $viewer = T::staff(['admin.access', 'pricing.view']);
    $list = P::priceList(['code' => 'base'], []);
    $this->actingAs($viewer, 'staff')->get($this->base)->assertOk();
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => []])->assertForbidden();
});

it('sửa nhanh giá niêm yết cho mọi size mà không đổi bảng giá khác', function () {
    Event::fake([PriceChanged::class]);
    $base = P::priceList(['code' => 'base'], [
        $this->s->id => [590_000, 690_000],
        $this->m->id => [590_000, 690_000],
    ]);
    $sale = P::priceList(['code' => 'sale', 'type' => 'sale'], [$this->s->id => [500_000]]);

    $this->put(route('admin.pricing.base-prices.style', ['styleId' => $this->style->id]), ['amount' => 550_000])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(Price::query()->where('price_list_id', $base->id)->orderBy('variant_id')->get(['variant_id', 'amount', 'compare_at_amount'])->toArray())
        ->toBe([
            ['variant_id' => $this->s->id, 'amount' => 550_000, 'compare_at_amount' => 690_000],
            ['variant_id' => $this->m->id, 'amount' => 550_000, 'compare_at_amount' => 690_000],
        ])
        ->and(Price::query()->where('price_list_id', $sale->id)->value('amount'))->toBe(500_000)
        ->and(PriceHistory::query()->where('price_list_id', $base->id)->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'pricing.prices.updated')->count())->toBe(1);
    Event::assertDispatchedTimes(PriceChanged::class, 1);
});

it('hiển thị giá hiện tại và URL sửa nhanh trên danh sách sản phẩm', function () {
    P::priceList(['code' => 'base'], [
        $this->s->id => [590_000],
        $this->m->id => [590_000],
    ]);
    $staff = T::staff(['admin.access', 'catalog.view', 'pricing.view', 'pricing.manage', 'inventory.view']);

    $this->actingAs($staff, 'staff')->get('/admin/catalog/products')->assertInertia(fn (Assert $page) => $page
        ->component('Catalog::Products/Index')
        ->where('canManagePrices', true)
        ->where("extensions.editValues.{$this->style->id}.price", 590_000)
        ->where("extensions.urls.{$this->style->id}.price", route('admin.pricing.base-prices.style', ['styleId' => $this->style->id]))
        ->where("extensions.urls.{$this->style->id}.stock", route('admin.inventory.stock.index', ['style' => $this->style->style_code])));
});

it('từ chối giá nhanh không hợp lệ mà không thay đổi giá đã lưu', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [590_000]]);
    $url = route('admin.pricing.base-prices.style', ['styleId' => $this->style->id]);

    $this->put($url, ['amount' => 0])->assertSessionHasErrors('amount');
    expect(Price::query()->where('variant_id', $this->s->id)->value('amount'))->toBe(590_000);
});

it('ngăn người chỉ có quyền xem sửa giá nhanh', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [590_000]]);
    $url = route('admin.pricing.base-prices.style', ['styleId' => $this->style->id]);
    $viewer = T::staff(['admin.access', 'pricing.view']);
    $this->actingAs($viewer, 'staff')->put($url, ['amount' => 550_000])->assertForbidden();

    expect(Price::query()->where('variant_id', $this->s->id)->value('amount'))->toBe(590_000);
});

it('trả về 404 khi sửa giá cho sản phẩm không tồn tại', function () {
    $this->actingAs(T::staff(['admin.access', 'pricing.manage']), 'staff')
        ->put(route('admin.pricing.base-prices.style', ['styleId' => 999_999]), ['amount' => 550_000])
        ->assertNotFound();

    expect(PriceList::query()->where('code', 'base')->exists())->toBeFalse();
});

it('tạo bảng giá chỉ cần loại, tên (và khung giờ): mã tự sinh từ tên, độ ưu tiên theo loại, vào thẳng trang làm việc', function () {
    $group = T::seed(fn () => DB::table('customer_groups')->insertGetId(['code' => 'vip', 'name' => 'VIP', 'created_at' => now(), 'updated_at' => now()]));

    $this->post($this->base, ['type' => 'sale', 'name' => 'Sale 11.11', 'status' => 'active'])->assertSessionHasNoErrors()
        ->assertRedirect("{$this->base}/".PriceList::query()->where('name', 'Sale 11.11')->value('id').'/prices');
    $this->post($this->base, ['type' => 'sale', 'name' => 'Sale 11.11', 'status' => 'active'])->assertSessionHasNoErrors();
    $this->post($this->base, ['type' => 'member', 'name' => 'Giá VIP', 'status' => 'active'])->assertSessionHasErrors('customer_group_id');
    $this->post($this->base, ['type' => 'member', 'name' => 'Giá VIP', 'customer_group_id' => $group, 'status' => 'active'])->assertSessionHasNoErrors();

    expect(PriceList::query()->orderBy('id')->get(['code', 'type', 'priority'])->map(fn (PriceList $list): array => [$list->code, $list->type->value, $list->priority])->all())
        ->toBe([['sale-1111', 'sale', 10], ['sale-1111-2', 'sale', 10], ['gia-vip', 'member', 20]]);
});

it('chỉ một bảng giá niêm yết: không tạo thêm; bảng niêm yết chính không đổi loại, không đổi mã', function () {
    $main = P::priceList(['code' => 'base'], []);

    $this->post($this->base, ['type' => 'base', 'name' => 'Giá pro', 'code' => 'pro', 'status' => 'active'])->assertSessionHasErrors('type');
    $this->put("{$this->base}/{$main->id}", ['type' => 'sale', 'name' => 'X', 'status' => 'active', 'lock_version' => 0])->assertSessionHasErrors('type');
    $this->put("{$this->base}/{$main->id}", ['type' => 'base', 'name' => 'X', 'code' => 'gia-chinh', 'status' => 'active', 'lock_version' => 0])->assertSessionHasErrors('code');
    $this->put("{$this->base}/{$main->id}", ['type' => 'base', 'name' => 'Giá bán lẻ', 'status' => 'active', 'lock_version' => 0])->assertSessionHasNoErrors();

    expect($main->fresh()->only(['code', 'name', 'priority']))->toBe(['code' => 'base', 'name' => 'Giá bán lẻ', 'priority' => 0]);
});

it('bảng giá niêm yết chỉ xem ở trang bảng giá: không sửa trong ô, không áp hàng loạt, không gỡ — sửa ở sản phẩm', function () {
    $main = P::priceList(['code' => 'base'], [$this->s->id => [300_000]]);

    $this->get("{$this->base}/{$main->id}/prices")->assertInertia(fn (Assert $page) => $page->where('readOnly', true)->has('rows', 1));
    $this->put("{$this->base}/{$main->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 1]]])->assertSessionHasErrors('business');
    $this->post("{$this->base}/{$main->id}/bulk", ['scope' => 'all', 'mode' => 'percent', 'value' => 10, 'round_to' => 0])->assertSessionHasErrors('business');
    $this->post("{$this->base}/{$main->id}/remove", ['variant_ids' => [$this->s->id]])->assertSessionHasErrors('business');

    expect(Price::query()->where('price_list_id', $main->id)->value('amount'))->toBe(300_000);
});

it('danh sách sản phẩm chỉ cho chọn bảng sale hoặc thành viên và giữ đường dẫn mở trang bảng giá cũ', function () {
    $main = P::priceList(['code' => 'base'], []);
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'pricing.view', 'pricing.manage']), 'staff');
    $products = fn (): array => collect($this->get('/admin/catalog/products')->viewData('page')['props']['extensions']['actions'])->keyBy('key')->all();
    expect($products())->not->toHaveKey('add_to_price_list');
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);
    $member = P::priceList(['type' => 'member'], []);

    $action = $products()['add_to_price_list'];

    expect(array_column($action['fields'][0]['options'], 'value'))->toBe([(string) $member->id, (string) $sale->id])
        ->and($action['preview_url'])->toBe(route('admin.pricing.price-lists.bulk-from-products.preview'));
    $legacy = route('admin.pricing.price-lists.add-products');
    $this->post($legacy, ['ids' => [$this->style->id], 'price_list_id' => $sale->id])->assertRedirect("{$this->base}/{$sale->id}/prices?add={$this->style->id}");
    $this->post($legacy, ['ids' => [$this->style->id], 'price_list_id' => $main->id])->assertSessionHasErrors('price_list_id');
    $this->get("{$this->base}/{$sale->id}/prices?add={$this->style->id},x,0")->assertInertia(fn (Assert $page) => $page->where('addStyleIds', [$this->style->id]));
});

it('xem trước rồi áp giá ngay tại danh sách sản phẩm chỉ ghi bảng và SKU đã chọn', function () {
    $outside = P::variants(T::product($this->brand->id, ['style_code' => 'LM-OTHER']), ['S'])[0];
    $base = P::priceList(['code' => 'base'], [$this->s->id => [499_000], $outside->id => [300_000]]);
    $sale = P::priceList(['type' => 'sale'], [$this->s->id => [450_000], $outside->id => [275_000]]);
    $member = P::priceList(['type' => 'member'], [$this->s->id => [420_000]]);
    $url = "{$this->base}/bulk-from-products";
    $payload = [
        'ids' => [$this->style->id], 'price_list_id' => $sale->id, 'mode' => 'percent', 'value' => 30, 'round_to' => 1000,
        'scope' => 'all', 'style_ids' => [$outside->style_id],
    ];

    $this->postJson("{$url}/preview", $payload)->assertOk()
        ->assertJsonPath('message', 'Bảng Bảng giá · 1/2 SKU đổi giá, bỏ qua 1 SKU.')
        ->assertJsonPath('can_apply', true)->assertJsonPath('warning', true)
        ->assertJsonFragment(["{$this->s->sku}: 450.000 ₫ → 349.000 ₫"]);
    expect($sale->prices()->where('variant_id', $this->s->id)->value('amount'))->toBe(450_000);

    $catalog = '/admin/catalog/products?q=LM-SH01';
    $this->from($catalog)->post($url, $payload)->assertRedirect($catalog)
        ->assertSessionHas('success', 'Đã cập nhật giá 1 SKU. Bỏ qua 1 SKU chưa có giá niêm yết.');
    expect($sale->prices()->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 349_000, $outside->id => 275_000])
        ->and($base->prices()->where('variant_id', $this->s->id)->value('amount'))->toBe(499_000)
        ->and($member->prices()->where('variant_id', $this->s->id)->value('amount'))->toBe(420_000)
        ->and(PriceHistory::query()->where('price_list_id', $sale->id)->count())->toBe(1);
    $this->postJson("{$url}/preview", $payload)->assertOk()->assertJsonPath('can_apply', false);

    $fixedMember = [...$payload, 'price_list_id' => $member->id, 'mode' => 'fixed', 'value' => 299_900];
    $this->post($url, $fixedMember)->assertSessionHasNoErrors();
    expect($member->prices()->orderBy('variant_id')->pluck('amount')->all())->toBe([299_900, 299_900]);
});

it('áp giá từ sản phẩm xác thực quyền, bảng đích và giá trị trước khi ghi', function () {
    $base = P::priceList(['code' => 'base'], [$this->s->id => [300_000]]);
    $sale = P::priceList(['type' => 'sale'], []);
    $url = "{$this->base}/bulk-from-products";
    $payload = ['ids' => [$this->style->id], 'price_list_id' => $sale->id, 'mode' => 'percent', 'value' => 10, 'round_to' => 1000];

    $this->postJson("{$url}/preview", [...$payload, 'price_list_id' => $base->id])->assertUnprocessable()->assertJsonValidationErrors('price_list_id');
    $this->post($url, [...$payload, 'ids' => [], 'value' => 101])->assertSessionHasErrors(['style_ids', 'value']);
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'pricing.view']), 'staff');
    $this->postJson("{$url}/preview", $payload)->assertForbidden();
    $this->post($url, $payload)->assertForbidden();
    expect($sale->prices()->count())->toBe(0);
});

it('xuất file bảng giá: SKU, sản phẩm, tuỳ chọn, giá niêm yết, giá, giá gạch — dùng làm mẫu nhập lại', function () {
    P::priceList(['code' => 'base'], [$this->s->id => [300_000], $this->m->id => [300_000]]);
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale'], [$this->s->id => [199_000, 250_000]]);

    $response = $this->get("{$this->base}/{$sale->id}/export")->assertDownload();
    $rows = SimpleXlsx::read($response->baseResponse->getFile()->getPathname());

    expect($rows[0])->toBe(PriceListFile::HEADER)
        ->and($rows)->toHaveCount(2)
        ->and([$rows[1][0], $rows[1][3], $rows[1][4], $rows[1][5]])->toBe([$this->s->sku, '300000', '199000', '250000']);
});

it('nhập file: cập nhật/thêm theo SKU, 0đ hợp lệ, ô trống giữ nguyên; dòng lỗi được báo, dòng khác vẫn ghi', function () {
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale'], [$this->s->id => [199_000]]);
    $csv = implode("\n", [
        'SKU;Sản phẩm;Giá;Giá gạch',
        "{$this->s->sku};Áo;189.000;",
        "{$this->m->sku};Áo;0;",
        'KHONG-CO;X;100000;',
        "{$this->s->sku}-x;X;;",
        "{$this->m->sku};Áo;199.5;",
        "{$this->m->sku};Áo;100000;90000",
    ]);

    $this->post("{$this->base}/{$sale->id}/import", ['file' => UploadedFile::fake()->createWithContent('gia.csv', $csv)])
        ->assertSessionHas('success', 'Đã nhập file: cập nhật 2 giá, 0 giá không đổi, 3 dòng lỗi.')
        ->assertSessionHasErrors(['import.0' => 'Dòng 6 ('.$this->m->sku.'): giá không hợp lệ — nhập số đồng, vd. 199000 hoặc 199.000.']);

    expect(Price::query()->where('price_list_id', $sale->id)->orderBy('variant_id')->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 189_000, $this->m->id => 0]);
});

it('nhập file: thiếu cột SKU/Giá bị từ chối; bảng niêm yết không nhập ở đây', function () {
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale'], []);
    $main = P::priceList(['code' => 'base'], []);

    $this->post("{$this->base}/{$sale->id}/import", ['file' => UploadedFile::fake()->createWithContent('gia.csv', "Mã;Tiền\nA;1")])->assertSessionHasErrors('file');
    $this->post("{$this->base}/{$main->id}/import", ['file' => UploadedFile::fake()->createWithContent('gia.csv', "SKU;Giá\n{$this->s->sku};1")])->assertSessionHasErrors('business');
    expect(Price::query()->count())->toBe(0);
});

it('bảng niêm yết thừa (dữ liệu cũ): danh sách cảnh báo; sửa phải chọn Sale/Thành viên', function () {
    P::priceList(['code' => 'base'], []);
    $extra = P::priceList(['code' => 'pro'], []);

    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->where('extraBaseLists', [['id' => $extra->id, 'name' => 'Bảng giá', 'code' => 'pro']]));
    $this->get("{$this->base}/{$extra->id}/edit")->assertInertia(fn (Assert $page) => $page->where('types', ['sale', 'member']));
    $this->put("{$this->base}/{$extra->id}", ['type' => 'base', 'name' => 'Pro', 'status' => 'active', 'lock_version' => 0])->assertSessionHasErrors('type');
    $this->put("{$this->base}/{$extra->id}", ['type' => 'sale', 'name' => 'Pro', 'status' => 'active', 'lock_version' => 0])->assertSessionHasNoErrors();

    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->where('extraBaseLists', []));
    expect($extra->fresh()->type->value)->toBe('sale');
});
