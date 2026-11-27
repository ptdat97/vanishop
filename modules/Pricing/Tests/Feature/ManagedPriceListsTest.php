<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Contracts\Data\ManagedPriceList;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/PricingTestHelpers.php';

beforeEach(function () {
    [$this->s, $this->m] = P::variants(T::product());
    P::priceList(['code' => 'base'], [$this->s->id => [300_000], $this->m->id => [200_000]]);
    $this->managed = app(ManagedPriceLists::class);
    Route::get('/admin/test-plugin/campaigns/{key}', fn () => 'ok')->name('test.campaigns.edit');
    app('router')->getRoutes()->refreshNameLookups();
    $this->sync = fn (array $prices, bool $active = true) => $this->managed->sync('vani.pricing-campaign', '7', new ManagedPriceList(
        'Flash sale 12.12', ManagedPriceList::TYPE_SALE, $active, $prices, priority: 30, editRoute: 'test.campaigns.edit',
    ));
});

it('đồng bộ bảng giá sale của plugin: giá hiệu lực có giá gạch là giá niêm yết, mã cố định; gỡ variant không còn; có lịch sử giá', function () {
    $id = ($this->sync)([$this->s->id => 150_000, $this->m->id => 180_000]);
    $code = $this->managed->lists('vani.pricing-campaign')[7]['code'];

    $price = app(PriceResolver::class)->forVariants([$this->s->id], new PricingContext(now()->getTimestamp()))[$this->s->id];
    expect([$price->amount->amount, $price->compareAt?->amount, $price->priceListCode])->toBe([150_000, 300_000, $code])
        ->and($code)->toBe('vani-pricing-campaign-7');

    expect(($this->sync)([$this->s->id => 160_000]))->toBe($id);
    expect(T::seed(fn () => PriceList::query()->findOrFail($id))->prices()->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 160_000])
        ->and(PriceHistory::query()->where('price_list_id', $id)->count())->toBe(4);

    $this->managed->remove('vani.pricing-campaign', '7');
    expect(T::seed(fn () => PriceList::query()->whereKey($id)->exists()))->toBeFalse();
});

it('từ chối giá thấp hơn mức giảm tối đa so với giá niêm yết; bảng giá thành viên cần nhóm khách', function () {
    expect(fn () => ($this->sync)([$this->s->id => 149_999]))->toThrow(ValidationException::class, 'Giá thấp hơn mức giảm tối đa 50%');
    expect(fn () => $this->managed->sync('vani.pricing-campaign', '8', new ManagedPriceList('Thành viên', ManagedPriceList::TYPE_MEMBER, true, [])))
        ->toThrow(ValidationException::class);
    expect(PriceList::query()->whereNotNull('managed_by')->count())->toBe(0);
});

it('Admin: bảng giá của plugin chỉ xem — sửa/xoá/đổi giá bị chặn, mở sửa thì sang màn hình plugin; không gắn được vào campaign', function () {
    $id = ($this->sync)([$this->s->id => 200_000]);
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage']), 'staff');

    $this->get("/admin/pricing/price-lists/{$id}/edit")->assertRedirect('/admin/test-plugin/campaigns/7');
    $this->put("/admin/pricing/price-lists/{$id}/prices", ['prices' => [['variant_id' => $this->s->id, 'amount' => 250_000]]])->assertSessionHasErrors('business');
    $this->delete("/admin/pricing/price-lists/{$id}")->assertSessionHasErrors('business');
    expect(T::seed(fn () => PriceList::query()->findOrFail($id))->prices()->value('amount'))->toBe(200_000)
        ->and(collect(app(PriceListSchedule::class)->schedulable())->pluck('id')->all())->not->toContain($id);
});
