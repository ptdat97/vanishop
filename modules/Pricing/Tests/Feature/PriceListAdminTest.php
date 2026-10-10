<?php

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
    $list = P::priceList(['code' => 'base'], []);

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
    $list = P::priceList(['code' => 'base'], [$this->s->id => [590_000]]);

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 590000, 'compare_at_amount' => null]]])->assertSessionHasNoErrors();

    expect(PriceHistory::query()->count())->toBe(0);
});

it('từ chối giá gốc không lớn hơn giá bán, số âm và biến thể không tồn tại', function () {
    $list = P::priceList(['code' => 'base'], []);

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 500000, 'compare_at_amount' => 500000]]])->assertSessionHasErrors('prices.0.compare_at_amount');
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => -1]]])->assertSessionHasErrors('prices.0.amount');
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => 999_999, 'amount' => 1000]]])->assertSessionHasErrors('prices.0.variant_id');
});

it('lưới giá hiển thị biến thể theo mã sản phẩm', function () {
    $list = P::priceList(['code' => 'base'], [$this->s->id => [590_000]]);

    $this->get("{$this->base}/{$list->id}/prices?style=LM-SH01")->assertInertia(fn (Assert $page) => $page
        ->component('Pricing::PriceLists/Prices')->has('rows', 2)->where('rows.0.amount', 590000)->where('rows.1.amount', null));
});

it('quyền chỉ xem không sửa được giá', function () {
    $viewer = T::staff(['admin.access', 'pricing.view']);
    $list = P::priceList(['code' => 'base'], []);
    $this->actingAs($viewer, 'staff')->get($this->base)->assertOk();
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => []])->assertForbidden();
});
