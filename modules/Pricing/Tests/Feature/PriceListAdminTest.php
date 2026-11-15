<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Pricing\Events\PriceChanged;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

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

    $this->getJson("{$this->base}/{$sale->id}/candidates?q=lm-sh")->assertOk()->assertJsonPath('data.0.style_id', $this->style->id)->assertJsonPath('data.0.variants', 2)->assertJsonPath('data.0.in_list', 0);
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

it('danh sách sản phẩm → "Đưa vào bảng giá…": chỉ bảng sale/thành viên; mở trang làm việc với sản phẩm đã chọn', function () {
    $main = P::priceList(['code' => 'base'], []);
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'pricing.view', 'pricing.manage']), 'staff');
    $products = fn (): array => collect($this->get('/admin/catalog/products')->viewData('page')['props']['extensions']['actions'])->keyBy('key')->all();
    expect($products())->not->toHaveKey('add_to_price_list');
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);

    $action = $products()['add_to_price_list'];

    expect(array_column($action['fields'][0]['options'], 'value'))->toBe([(string) $sale->id]);
    $this->post($action['url'], ['ids' => [$this->style->id], 'price_list_id' => $sale->id])->assertRedirect("{$this->base}/{$sale->id}/prices?add={$this->style->id}");
    $this->post($action['url'], ['ids' => [$this->style->id], 'price_list_id' => $main->id])->assertSessionHasErrors('price_list_id');
    $this->get("{$this->base}/{$sale->id}/prices?add={$this->style->id},x,0")->assertInertia(fn (Assert $page) => $page->where('addStyleIds', [$this->style->id]));
});
