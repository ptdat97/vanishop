<?php

use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Pricing\Events\PriceChanged;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/PricingTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->web = Channel::factory()->forBrand($this->brand, 'vani.test', '/lumiere')->create();
    $this->foreignChannel = Channel::factory()->forBrand($this->other, 'vani.test', '/urbanx')->create();
    $this->style = T::product($this->brand->id, ['style_code' => 'LM-SH01']);
    [$this->s, $this->m] = P::variants($this->style);
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'pricing.view', 'pricing.manage']), 'staff');
    $this->base = '/admin/pricing/lumiere/price-lists';
});

function listPayload(array $overrides = []): array
{
    return array_replace(['code' => 'base', 'name' => 'Giá niêm yết', 'type' => 'base', 'priority' => 0, 'status' => 'active', 'channel_ids' => []], $overrides);
}

it('tạo bảng giá và gán kênh của brand', function () {
    $this->post($this->base, listPayload(['channel_ids' => [$this->web->id]]))->assertSessionHasNoErrors();

    $list = T::seed(fn () => PriceList::query()->sole());
    $this->get("{$this->base}/{$list->id}/edit")->assertInertia(fn (Assert $page) => $page->component('Pricing::PriceLists/Form')->where('priceList.channel_ids', [$this->web->id]));
});

it('không gán được kênh không bán brand; khung giờ phải hợp lệ; mã duy nhất', function () {
    $this->post($this->base, listPayload(['channel_ids' => [$this->foreignChannel->id]]))->assertSessionHasErrors('channel_ids');
    $this->post($this->base, listPayload(['starts_at' => '2026-11-12 00:00', 'ends_at' => '2026-11-11 00:00']))->assertSessionHasErrors('ends_at');
    $this->post($this->base, listPayload());
    $this->post($this->base, listPayload())->assertSessionHasErrors('code');
});

it('nhập giá hàng loạt, ghi lịch sử giá, audit và phát PriceChanged', function () {
    Event::fake([PriceChanged::class]);
    $list = P::priceList($this->brand->id, ['code' => 'base'], [$this->web->id], []);

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
    $list = P::priceList($this->brand->id, ['code' => 'base'], [$this->web->id], [$this->s->id => [590_000]]);

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 590000, 'compare_at_amount' => null]]])->assertSessionHasNoErrors();

    expect(PriceHistory::query()->count())->toBe(0);
});

it('từ chối giá gốc không lớn hơn giá bán, số âm và biến thể của brand khác', function () {
    $list = P::priceList($this->brand->id, ['code' => 'base'], [$this->web->id], []);
    [$foreignVariant] = P::variants(T::product($this->other->id));

    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 500000, 'compare_at_amount' => 500000]]])->assertSessionHasErrors('prices.0.compare_at_amount');
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => -1]]])->assertSessionHasErrors('prices.0.amount');
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => [['variant_id' => $foreignVariant->id, 'amount' => 1000]]])->assertSessionHasErrors('prices.0.variant_id');
});

it('lưới giá hiển thị biến thể theo mã sản phẩm', function () {
    $list = P::priceList($this->brand->id, ['code' => 'base'], [$this->web->id], [$this->s->id => [590_000]]);

    $this->get("{$this->base}/{$list->id}/prices?style=LM-SH01")->assertInertia(fn (Assert $page) => $page
        ->component('Pricing::PriceLists/Prices')->has('rows', 2)->where('rows.0.amount', 590000)->where('rows.1.amount', null));
});

it('cô lập brand và quyền chỉ xem', function () {
    $foreign = P::priceList($this->other->id, ['code' => 'ux'], [], []);
    $this->get('/admin/pricing/urbanx/price-lists')->assertNotFound();
    $this->get("{$this->base}/{$foreign->id}/edit")->assertNotFound();

    $viewer = T::staffFor($this->brand, ['admin.access', 'pricing.view']);
    $list = P::priceList($this->brand->id, ['code' => 'base'], [], []);
    $this->actingAs($viewer, 'staff')->get($this->base)->assertOk();
    $this->put("{$this->base}/{$list->id}/prices", ['prices' => []])->assertForbidden();
});
