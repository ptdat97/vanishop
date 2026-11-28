<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as Checkout;
use Modules\Customer\Persistence\Models\CustomerGroup;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\StorefrontApiContract;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceQuotas;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Promotion\Persistence\Models\Campaign;
use Plugin\PricingCampaign\Infrastructure\CampaignBlock;
use Plugin\PricingCampaign\Persistence\PricingCampaign;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;

require_once dirname(__DIR__, 5).'/modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    Catalog::seed(function () {
        $plugins = app(PluginManager::class);
        $plugins->install(PricingCampaignServiceProvider::ID);
        $plugins->enable(PricingCampaignServiceProvider::ID);
    });
    app()->register(PricingCampaignServiceProvider::class);
    app(PluginActivation::class)->flush();
    $this->freezeTime();

    ['s' => $this->s, 'm' => $this->m] = Checkout::store(); // Đầm lụa: S 300.000, M 200.000
    $this->base = '/admin/plugins/vani-pricing-campaign';
    $this->price = fn ($variant): int => app(PriceResolver::class)->forVariants([$variant->id], new PricingContext(now()->getTimestamp()))[$variant->id]->amount->amount;
    $this->payload = fn (array $overrides = [], array $item = []) => array_replace([
        'template' => 'flash_sale', 'name' => 'Flash sale 12.12', 'label' => 'Flash sale', 'badge' => 'Flash sale', 'content' => '', 'badge_style' => 'danger',
        'accent_color' => null, 'banner_media_id' => null, 'show_countdown' => true, 'show_stock' => true, 'priority' => 40, 'customer_group_id' => null,
        'starts_at' => now()->subHour()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDay()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        'recurrence' => null, 'quota_total' => null, 'per_customer_total' => null, 'status' => 'active',
        'items' => [array_replace(['product_id' => $this->s->product_id, 'strategy' => 'percent', 'value' => 20, 'quota' => 10, 'per_customer' => 2], $item)],
    ], $overrides);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'pricing_campaign.view', 'pricing_campaign.manage', 'media.view'])->create(), 'staff');
});

it('lưu chiến dịch → bảng giá của plugin (giá từng size từ giá niêm yết), suất theo sản phẩm, Campaign của Core; giá sale có hiệu lực', function () {
    $this->post($this->base, ($this->payload)())->assertSessionHasNoErrors();

    $campaign = PricingCampaign::query()->sole();
    $list = Catalog::seed(fn () => PriceList::query()->findOrFail($campaign->price_list_id));
    expect($list->managed_by)->toBe(PricingCampaignServiceProvider::ID)
        ->and($list->prices()->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 240_000, $this->m->id => 160_000])
        ->and(($this->price)($this->s))->toBe(240_000)
        ->and(app(PriceQuotas::class)->status($list->id)[0])->scope->toBe('product')->limit->toBe(10)->perCustomerLimit->toBe(2)
        ->and(Campaign::query()->findOrFail($campaign->campaign_id))->managed_by->toBe(PricingCampaignServiceProvider::ID)->status->toBe('active');

    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->component('PricingCampaign::Index')
        ->where('campaigns.0.state', 'running')->where('campaigns.0.items_count', 1));
});

it('từ chối: giảm vượt mức tối đa của cửa hàng (báo ở dòng sản phẩm), đồng giá không thấp hơn giá niêm yết, kết thúc trước bắt đầu, nhân viên chỉ xem', function () {
    $this->post($this->base, ($this->payload)(item: ['value' => 60]))->assertSessionHasErrors('items.0.value');
    $this->post($this->base, ($this->payload)(item: ['strategy' => 'fixed_price', 'value' => 400_000]))->assertSessionHasErrors('items.0.value');
    $this->post($this->base, ($this->payload)(['ends_at' => now()->subDays(2)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i')]))->assertSessionHasErrors('ends_at');
    expect(PricingCampaign::query()->count())->toBe(0)
        ->and(Catalog::seed(fn () => PriceList::query()->whereNotNull('managed_by')->count()))->toBe(0);

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'pricing_campaign.view'])->create(), 'staff');
    $this->post($this->base, ($this->payload)())->assertForbidden();
});

it('giá niêm yết đổi → giá chiến dịch tính lại; xoá chiến dịch gỡ bảng giá và Campaign', function () {
    $this->post($this->base, ($this->payload)())->assertSessionHasNoErrors();
    $campaign = PricingCampaign::query()->sole();

    Catalog::seed(fn () => app(PriceListService::class)->setPrices(PriceList::query()->where('code', 'base')->firstOrFail(), [
        ['variant_id' => $this->s->id, 'amount' => 500_000, 'compare_at_amount' => null],
    ]));
    expect(($this->price)($this->s))->toBe(400_000);

    $this->delete("{$this->base}/{$campaign->id}")->assertRedirect();
    expect(Catalog::seed(fn () => PriceList::query()->whereKey($campaign->price_list_id)->exists()))->toBeFalse()
        ->and(Campaign::query()->whereKey($campaign->campaign_id)->exists())->toBeFalse()
        ->and(($this->price)($this->s))->toBe(500_000);
});

it('storefront: trang sản phẩm có đếm ngược và suất còn lại, trang Ưu đãi + API + block liệt kê chiến dịch; ngoài khung giờ thì giá về giá thường', function () {
    $this->post($this->base, ($this->payload)())->assertSessionHasNoErrors();
    auth('staff')->logout();
    $pluginDir = dirname(__DIR__, 2);

    $this->get(route('storefront.product', $this->s->product->slug))->assertOk()
        ->assertSee('data-vani-campaign-offer', false)->assertSee('data-vani-countdown', false)->assertSee('Còn 10 suất')->assertSee('Mỗi khách tối đa 2');
    $this->get('/uu-dai')->assertOk()->assertSee('Flash sale')->assertSee('data-vani-campaign=', false)->assertSee('data-vani-campaign-badge', false);
    StorefrontApiContract::assertRoutesDocumented($pluginDir);
    StorefrontApiContract::assertResponse($this->getJson('/api/storefront/v1/x/vani-pricing-campaign/campaigns'), $pluginDir, '/campaigns', 'GET')
        ->assertJsonPath('data.0.running', true)->assertJsonPath('data.0.products.0.id', $this->s->product_id);
    StorefrontApiContract::assertResponse($this->getJson("/api/storefront/v1/x/vani-pricing-campaign/products/{$this->s->product_id}"), $pluginDir, '/products/{product}', 'GET')
        ->assertJsonPath('data.remaining', 10)->assertJsonPath('data.per_customer', 2);
    expect(app(CampaignBlock::class)->resolve(['campaign' => '0'], 'vi')['campaign']['label'])->toBe('Flash sale');

    // Chỉ chạy khung 01:00–02:00 hằng ngày (giờ cửa hàng) → ngoài khung.
    $campaign = PricingCampaign::query()->sole();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'pricing_campaign.view', 'pricing_campaign.manage'])->create(), 'staff');
    $closed = now()->timezone('Asia/Ho_Chi_Minh')->addHours(3)->format('H:00');
    $closedEnd = now()->timezone('Asia/Ho_Chi_Minh')->addHours(4)->format('H:00');
    $this->put("{$this->base}/{$campaign->id}", [...($this->payload)(['recurrence' => [['days' => [1, 2, 3, 4, 5, 6, 7], 'from' => $closed, 'to' => $closedEnd]]]), 'lock_version' => $campaign->lock_version])
        ->assertSessionHasNoErrors();
    expect(($this->price)($this->s))->toBe(300_000)
        ->and($campaign->fresh()->state(now()->getTimestamp()))->toBe('waiting');
});

it('trang Ưu đãi lọc đang chạy / sắp chạy, sắp xếp và render Markdown an toàn', function () {
    $this->post($this->base, ($this->payload)([
        'name' => 'Đang chạy', 'label' => 'Đang chạy',
        'content' => "**Giảm sâu**\n<script>alert(1)</script>",
        'ends_at' => now()->addHours(5)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
    ]))->assertSessionHasNoErrors();

    $this->post($this->base, ($this->payload)([
        'name' => 'Sắp chạy', 'label' => 'Sắp chạy',
        'starts_at' => now()->addHours(2)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
        'ends_at' => now()->addHours(6)->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'),
    ]))->assertSessionHasNoErrors();

    auth('staff')->logout();

    $this->get('/uu-dai')->assertOk()
        ->assertSee('<strong>Giảm sâu</strong>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Đang chạy')
        ->assertSee('Sắp chạy');

    $this->get('/uu-dai?status=running')->assertOk()->assertSee('Đang chạy')->assertDontSee('Sắp chạy');
    $this->get('/uu-dai?status=upcoming')->assertOk()->assertSee('Sắp chạy')->assertDontSee('Đang chạy');
    $this->getJson('/api/storefront/v1/x/vani-pricing-campaign/campaigns?status=running&sort=low_stock')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.label', 'Đang chạy');
});

it('giá thành viên chỉ áp dụng cho khách thuộc nhóm khách hàng đã chọn', function () {
    $group = Catalog::seed(fn () => CustomerGroup::query()->create([
        'code' => 'vip', 'name' => 'VIP',
    ]));

    $this->post($this->base, ($this->payload)([
        'template' => 'member', 'name' => 'Giá VIP', 'customer_group_id' => $group->id,
    ]))->assertSessionHasNoErrors();

    // Khách công khai không thấy giá VIP.
    expect(($this->price)($this->s))->toBe(300_000);

    // Khách thuộc nhóm VIP thấy giá 240.000.
    $memberPrice = app(PriceResolver::class)->forVariants([$this->s->id], new PricingContext(now()->getTimestamp(), customerGroupId: $group->id))[$this->s->id]->amount->amount;
    expect($memberPrice)->toBe(240_000);
});

it('badge trên thẻ sản phẩm chỉ hiện khi bảng giá chiến dịch đang thắng giá (L79)', function () {
    $this->post($this->base, ($this->payload)(['priority' => 10]))->assertSessionHasNoErrors();

    // Tạo bảng giá sale ưu tiên cao hơn (priority 50) với giá thấp hơn → thẻ sản phẩm dùng bảng giá đó, không hiện badge chiến dịch.
    Catalog::seed(function () {
        $list = PriceList::query()->create([
            'code' => 'higher-sale', 'name' => 'Higher Sale', 'type' => 'sale', 'priority' => 50,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'status' => 'active',
        ]);
        app(PriceListService::class)->setPrices($list, [
            ['variant_id' => $this->s->id, 'amount' => 180_000, 'compare_at_amount' => null],
            ['variant_id' => $this->m->id, 'amount' => 120_000, 'compare_at_amount' => null],
        ]);
    });

    auth('staff')->logout();

    $this->get('/uu-dai')->assertOk()->assertDontSee('data-vani-campaign-badge', false);
});
