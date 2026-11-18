<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Promotion\Persistence\Models\Campaign;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\MoneyFormatter;
use Plugin\Reports\ReportsServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Tab "Kết quả" trên trang chiến dịch: Promotion chỉ công bố số liệu của mình qua
| `Campaigns`; plugin báo cáo ghép với `OrderStatistics`. Không bật plugin → trang chiến dịch không có tab.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(ReportsServiceProvider::ID);
        app(PluginManager::class)->enable(ReportsServiceProvider::ID);
    });
    app()->register(ReportsServiceProvider::class);
    ['s' => $this->s] = C::store(); // giá chung S 300.000
    $this->sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10, 'status' => 'inactive'], [$this->s->id => [200_000, 300_000]]);
    $this->promotion = C::promotion(['name' => 'Giảm 10% 11.11']);
    T::seed(fn () => $this->promotion->update(['status' => 'inactive']));
    $this->base = '/admin/promotion/campaigns';
    $this->actingAs(T::staff(['admin.access', 'promotion.view', 'promotion.manage', 'pricing.manage', ReportsServiceProvider::PERMISSION]), 'staff');
    $this->start = function (array $promotionIds, array $priceListIds): Campaign {
        $this->post($this->base, [
            'code' => 'sale-1111', 'name' => 'Sale 11.11', 'promotion_ids' => $promotionIds, 'price_list_ids' => $priceListIds,
            'starts_at' => now()->subHour()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(3)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();
        $campaign = Campaign::query()->sole();
        $this->post("{$this->base}/{$campaign->id}/activate")->assertSessionHasNoErrors();

        return $campaign;
    };
    $this->order = function (int $expectedTotal, string $key): void {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers);
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => $expectedTotal]), [...$headers, 'Idempotency-Key' => $key])->assertCreated();
    };
    $this->results = fn (Campaign $campaign): array => collect(collect($this->get("{$this->base}/{$campaign->id}/edit")->viewData('page')['props']['extensions']['tabs'])
        ->firstWhere('key', 'vani.reports:results')['rows'] ?? [])->pluck('value', 'label')->all();
});

it('đơn dùng khuyến mãi của chiến dịch: số đơn, lượt dùng, doanh thu, tổng giảm', function () {
    $campaign = ($this->start)([$this->promotion->id], []);
    ($this->order)(270_000 + 30_000, 'campaign-0001');

    expect(($this->results)($campaign))->toMatchArray([
        'Đơn của chiến dịch' => '1', 'Lượt dùng khuyến mãi' => '1', 'Đơn hưởng giá sale/thành viên' => '0',
        'Doanh thu các đơn' => app(MoneyFormatter::class)->formatAmount(300_000, 'VND'),
        'Đã giảm (khuyến mãi)' => app(MoneyFormatter::class)->formatAmount(30_000, 'VND'),
    ]);
});

it('tính cả đơn chỉ hưởng giá sale của bảng giá chiến dịch; thiếu quyền báo cáo thì không thấy số', function () {
    $campaign = ($this->start)([], [$this->sale->id]);
    ($this->order)(200_000 + 30_000, 'campaign-0002');

    expect(($this->results)($campaign))->toMatchArray([
        'Đơn của chiến dịch' => '1', 'Lượt dùng khuyến mãi' => '0', 'Đơn hưởng giá sale/thành viên' => '1',
        'Doanh thu các đơn' => app(MoneyFormatter::class)->formatAmount(230_000, 'VND'),
    ]);

    $this->actingAs(T::staff(['admin.access', 'promotion.view', 'promotion.manage', 'pricing.manage']), 'staff');
    expect(($this->results)($campaign))->toBe(['Cần quyền xem báo cáo' => '']);
});
