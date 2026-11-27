<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Pricing\Contracts\Data\ManagedPriceList;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Promotion\Contracts\Campaigns;
use Modules\Promotion\Contracts\Data\ManagedCampaign;
use Modules\Promotion\Persistence\Models\Campaign;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Roadmap Phase 8: campaign = khuyến mãi + bảng giá sale/thành viên chạy chung lịch; campaign sở hữu lịch, kích hoạt /
| dừng khẩn cấp một nút. Kết quả chiến dịch (đơn, doanh thu): tab của vani.reports — custom/plugin/Reports/Tests.
*/

beforeEach(function () {
    $this->freezeTime();
    ['s' => $this->s] = C::store(); // giá chung S 300.000
    $this->sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10, 'status' => 'inactive'], [$this->s->id => [200_000, 300_000]]);
    $this->promotion = C::promotion(['name' => 'Giảm 10% 11.11']);
    T::seed(fn () => $this->promotion->update(['status' => 'inactive']));
    $this->base = '/admin/promotion/campaigns';
    $this->payload = fn (array $overrides = []) => [
        'code' => 'sale-1111', 'name' => 'Sale 11.11', 'starts_at' => now()->addDay()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDays(3)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        'promotion_ids' => [$this->promotion->id], 'price_list_ids' => [$this->sale->id], ...$overrides,
    ];
    $this->price = fn (): int => app(PriceResolver::class)->forVariants([$this->s->id], new PricingContext(now()->getTimestamp()))[$this->s->id]->amount->amount;
    $this->actingAs(T::staff(['admin.access', 'promotion.view', 'promotion.manage', 'pricing.manage']), 'staff');
});

it('nháp → thành viên tắt; kích hoạt → khuyến mãi + bảng giá chạy đúng lịch campaign; dừng khẩn cấp → tắt hết, không bật lại được', function () {
    $this->post($this->base, ($this->payload)())->assertRedirect();
    $campaign = Campaign::query()->sole();
    expect([$this->promotion->fresh()->status, $this->sale->fresh()->status])->toBe(['inactive', 'inactive'])
        ->and($campaign->state())->toBe('draft');

    $this->post("{$this->base}/{$campaign->id}/activate")->assertSessionHasNoErrors();
    $promotion = $this->promotion->fresh();
    $list = PriceList::query()->find($this->sale->id);
    expect([$promotion->status, $list->status])->toBe(['active', 'active'])
        ->and($promotion->starts_at->equalTo($campaign->starts_at))->toBeTrue()
        ->and($list->ends_at->equalTo($campaign->ends_at))->toBeTrue()
        ->and($campaign->fresh()->state())->toBe('scheduled')
        ->and(($this->price)())->toBe(300_000); // chưa tới giờ

    $this->travel(2)->days();
    expect($campaign->fresh()->state())->toBe('running')->and(($this->price)())->toBe(200_000);

    $this->post("{$this->base}/{$campaign->id}/stop")->assertSessionHasNoErrors();
    expect([$this->promotion->fresh()->status, PriceList::query()->find($this->sale->id)->status])->toBe(['inactive', 'inactive'])
        ->and(($this->price)())->toBe(300_000)
        ->and($campaign->fresh())->state()->toBe('stopped');

    $this->post("{$this->base}/{$campaign->id}/activate")->assertSessionHasErrors('campaign');
    $this->put("{$this->base}/{$campaign->id}", [...($this->payload)(), 'lock_version' => $campaign->fresh()->lock_version])->assertSessionHasErrors('campaign');
});

it('sửa lịch khi đang chạy → đồng bộ xuống thành viên; khoá lạc quan', function () {
    $this->post($this->base, ($this->payload)());
    $campaign = Campaign::query()->sole();
    $this->post("{$this->base}/{$campaign->id}/activate");

    $later = now()->addDays(10)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i');
    $this->put("{$this->base}/{$campaign->id}", [...($this->payload)(['ends_at' => $later]), 'lock_version' => $campaign->fresh()->lock_version])->assertSessionHasNoErrors();
    expect(PriceList::query()->find($this->sale->id)->ends_at->equalTo($campaign->fresh()->ends_at))->toBeTrue()
        ->and($this->promotion->fresh()->ends_at->equalTo($campaign->fresh()->ends_at))->toBeTrue();

    $this->put("{$this->base}/{$campaign->id}", [...($this->payload)(), 'lock_version' => 0])->assertSessionHasErrors('lock_version');
});

it('từ chối: lịch sai, bảng giá base, thành viên đã thuộc campaign khác; chỉ xoá campaign nháp (tách thành viên)', function () {
    $baseList = PriceList::query()->where('type', 'base')->firstOrFail();

    $this->post($this->base, ($this->payload)(['ends_at' => now()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i')]))->assertSessionHasErrors('ends_at');
    $this->post($this->base, ($this->payload)(['price_list_ids' => [$baseList->id]]))->assertSessionHasErrors('price_list_ids');

    $this->post($this->base, ($this->payload)());
    $first = Campaign::query()->sole();
    $this->post($this->base, ($this->payload)(['code' => 'khac']))->assertSessionHasErrors('promotion_ids');
    $this->post($this->base, ($this->payload)(['code' => 'khac', 'promotion_ids' => []]))->assertSessionHasErrors('price_list_ids');

    $this->delete("{$this->base}/{$first->id}")->assertRedirect();
    expect(Campaign::query()->count())->toBe(0)->and($this->promotion->fresh()->campaign_id)->toBeNull();
});

it('không có quyền bảng giá: sửa campaign không đụng tới danh sách bảng giá', function () {
    $this->post($this->base, ($this->payload)());
    $campaign = Campaign::query()->sole();

    $this->actingAs(T::staff(['admin.access', 'promotion.view', 'promotion.manage']), 'staff');
    $this->put("{$this->base}/{$campaign->id}", [...($this->payload)(['price_list_ids' => []]), 'lock_version' => $campaign->fresh()->lock_version])->assertSessionHasNoErrors();
    $this->get("{$this->base}/{$campaign->id}/edit")->assertInertia(fn (Assert $page) => $page->where('campaign.price_list_ids', [$this->sale->id])->where('can.price_lists', false));
});

it('lịch lặp cuối tuần của campaign ghi xuống khuyến mãi và bảng giá: giá sale và khuyến mãi chỉ chạy thứ Bảy, Chủ nhật', function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-04 10:00', 'Asia/Ho_Chi_Minh')); // thứ Sáu
    $weekend = [['days' => [6, 7], 'from' => '00:00', 'to' => '00:00']];
    $this->post($this->base, ($this->payload)([
        'starts_at' => '2026-12-04T09:00', 'ends_at' => '2026-12-31T23:00', 'recurrence' => $weekend,
    ]))->assertSessionHasNoErrors();
    $campaign = Campaign::query()->sole();
    $this->post("{$this->base}/{$campaign->id}/activate")->assertSessionHasNoErrors();

    expect(PriceList::query()->find($this->sale->id)->recurrence)->toBe($weekend)
        ->and($this->promotion->fresh()->recurrence)->toBe($weekend)
        ->and(($this->price)())->toBe(300_000)
        ->and($this->promotion->fresh()->isRunningAt(now()->getTimestamp()))->toBeFalse();

    $this->travelTo(CarbonImmutable::parse('2026-12-05 10:00', 'Asia/Ho_Chi_Minh')); // thứ Bảy
    expect(($this->price)())->toBe(200_000)
        ->and($this->promotion->fresh()->isRunningAt(now()->getTimestamp()))->toBeTrue();

    $this->put("{$this->base}/{$campaign->id}", [...($this->payload)(['recurrence' => [['days' => [9], 'from' => '00:00', 'to' => '00:00']]]), 'lock_version' => $campaign->fresh()->lock_version])
        ->assertSessionHasErrors('recurrence');
});

it('campaign do plugin quản lý: sync ghi lịch xuống bảng giá của plugin, báo cáo có mã bảng giá; Admin chỉ xem, sửa/kích hoạt/xoá bị chặn', function () {
    Route::get('/admin/test-plugin/deals/{key}', fn () => 'ok')->name('test.deals.edit');
    app('router')->getRoutes()->refreshNameLookups();
    $listId = app(ManagedPriceLists::class)->sync('vani.pricing-campaign', '3', new ManagedPriceList(
        'Flash sale', 'sale', true, [$this->s->id => 200_000],
    ));
    $campaigns = app(Campaigns::class);
    $id = $campaigns->sync('vani.pricing-campaign', '3', new ManagedCampaign(
        'Flash sale 12.12', 'active', now()->addDay()->getTimestamp(), now()->addDays(2)->getTimestamp(), priceListIds: [$listId], editRoute: 'test.deals.edit',
    ));

    expect(PriceList::query()->find($listId)->starts_at->getTimestamp())->toBe(now()->addDay()->getTimestamp())
        ->and($campaigns->usage($id)?->priceListCodes)->toBe(['vani-pricing-campaign-3'])
        ->and($campaigns->ids('vani.pricing-campaign'))->toBe([3 => $id])
        // Bảng giá của plugin khác không gắn được.
        ->and(fn () => $campaigns->sync('vani.khac', '1', new ManagedCampaign('X', 'active', now()->getTimestamp(), now()->addDay()->getTimestamp(), priceListIds: [$listId])))
        ->toThrow(ValidationException::class);

    $this->get("{$this->base}/{$id}/edit")->assertRedirect('/admin/test-plugin/deals/3');
    $this->post("{$this->base}/{$id}/stop")->assertSessionHasErrors('campaign');
    $this->delete("{$this->base}/{$id}")->assertSessionHasErrors('campaign');
    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->where('campaigns.0.managed', true)->where('campaigns.0.edit_url', url('/admin/test-plugin/deals/3')));

    $campaigns->remove('vani.pricing-campaign', '3');
    expect(Campaign::query()->whereKey($id)->exists())->toBeFalse();
});
