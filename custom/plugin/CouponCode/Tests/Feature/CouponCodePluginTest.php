<?php

use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as Checkout;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Customer\Persistence\Models\CustomerGroup;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as Customers;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\StorefrontApiContract;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as Inventory;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as Pricing;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;
use Plugin\CouponCode\CouponCodeServiceProvider;
use Plugin\CouponCode\Infrastructure\IssueCouponCodes;
use Plugin\CouponCode\Persistence\Coupon;
use Plugin\PromotionRules\PromotionRulesServiceProvider;

require_once dirname(__DIR__, 5).'/modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once dirname(__DIR__, 5).'/modules/Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    Catalog::seed(function () {
        $plugins = app(PluginManager::class);
        foreach (['vani.promotion-rules', CouponCodeServiceProvider::ID] as $id) {
            $plugins->install($id);
            $plugins->enable($id);
        }
    });
    app()->register(PromotionRulesServiceProvider::class);
    app()->register(CouponCodeServiceProvider::class);
    app(PluginActivation::class)->flush();

    // Đầm lụa (S 300.000đ, M 200.000đ) và Túi da 500.000đ.
    ['s' => $this->s, 'location' => $location] = Checkout::store();
    $bag = Catalog::product(null, ['name' => 'Túi da', 'slug' => 'tui-da']);
    [$this->bag] = Pricing::variants($bag, ['F']);
    Pricing::priceList([], [$this->bag->id => [500_000]]);
    Inventory::stock($location, $this->bag->id, 10);
    $this->base = '/admin/plugins/vani-coupon-code';
});

function manageCoupons($test, array $permissions = ['admin.access', 'coupon_code.view', 'coupon_code.manage', 'promotion.view']): void
{
    $test->actingAs(StaffUser::factory()->withPermissions($permissions)->create(), 'staff');
}

function couponPayload(array $overrides = []): array
{
    return array_replace([
        'kind' => 'shared', 'code' => 'giam10', 'name' => 'Giảm 10% đầm', 'description' => 'Giảm 10% cho đầm lụa.',
        'discount_type' => 'percent', 'discount_value' => 10, 'max_discount' => 20_000, 'min_subtotal' => null,
        'scope_type' => 'all', 'scope_values' => [], 'exclude_on_sale' => false, 'customer_groups' => [],
        'usage_limit' => 100, 'usage_per_customer' => 1, 'active' => true, 'exclusive' => false, 'public' => true,
    ], $overrides);
}

/** Giỏ qua Storefront API: trả [id, header]. */
function apiCart($test, int $variantId, int $quantity = 1): array
{
    $created = $test->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $test->postJson('/api/storefront/v1/carts/'.$created->json('data.id').'/lines', ['variant_id' => $variantId, 'quantity' => $quantity], $headers)->assertOk();

    return [$created->json('data.id'), $headers];
}

it('tạo mã dùng chung: đồng bộ khuyến mãi cần mã của Core (rule theo thứ tự, giảm tối đa, lượt mỗi khách) và tạo mã', function () {
    manageCoupons($this);

    $this->post($this->base, couponPayload([
        'scope_type' => 'products', 'scope_values' => [$this->s->product_id], 'exclude_on_sale' => true, 'min_subtotal' => 200_000,
    ]))->assertSessionHasNoErrors();

    $coupon = Coupon::query()->sole();
    $promotion = Catalog::seed(fn () => Promotion::query()->with('rules')->findOrFail($coupon->promotion_id));
    expect([$promotion->managed_by, $promotion->requires_voucher, $promotion->usage_limit, $promotion->usage_limit_per_customer])->toBe([CouponCodeServiceProvider::ID, true, 100, 1])
        ->and($promotion->action_config)->toBe(['basis_points' => 1000, 'max_amount' => 20_000])
        ->and($promotion->rules->pluck('rule_type')->all())->toBe(['in_products', 'exclude_on_sale', 'min_order_subtotal'])
        ->and(Voucher::query()->where('promotion_id', $promotion->id)->pluck('code')->all())->toBe(['GIAM10']);
});

it('từ chối % vượt mức, mã trùng, kết thúc trước bắt đầu; nhân viên chỉ xem không tạo được', function () {
    manageCoupons($this);
    $this->post($this->base, couponPayload())->assertSessionHasNoErrors();

    $this->post($this->base, couponPayload(['code' => 'KHAC', 'discount_value' => 95]))->assertSessionHasErrors('discount_value');
    $this->post($this->base, couponPayload())->assertSessionHasErrors('code');
    $this->post($this->base, couponPayload(['code' => 'KHAC', 'starts_at' => '2026-12-10T10:00', 'ends_at' => '2026-12-01T10:00']))->assertSessionHasErrors('ends_at');
    expect(Coupon::query()->count())->toBe(1);

    manageCoupons($this, ['admin.access', 'coupon_code.view']);
    $this->post($this->base, couponPayload(['code' => 'KHAC']))->assertForbidden();
    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->component('CouponCode::Index')->where('coupons.0.code', 'GIAM10')->where('can.manage', false));
});

it('sửa theo khoá lạc quan: bản cũ bị từ chối; xoá mã gỡ khuyến mãi', function () {
    manageCoupons($this);
    $this->post($this->base, couponPayload())->assertSessionHasNoErrors();
    $coupon = Coupon::query()->sole();

    $this->put("{$this->base}/{$coupon->id}", couponPayload(['kind' => null, 'code' => null, 'discount_value' => 15, 'lock_version' => 0]) + [])->assertSessionHasErrors('kind');
    $update = array_diff_key(couponPayload(['discount_value' => 15, 'lock_version' => 0]), ['kind' => 1, 'code' => 1]);
    $this->put("{$this->base}/{$coupon->id}", $update)->assertSessionHasNoErrors();
    $this->put("{$this->base}/{$coupon->id}", $update)->assertSessionHasErrors('lock_version');

    expect(Catalog::seed(fn () => Promotion::query()->findOrFail($coupon->promotion_id))->action_config['basis_points'])->toBe(1500);

    $this->delete("{$this->base}/{$coupon->id}")->assertRedirect();
    expect(Coupon::query()->count())->toBe(0)
        ->and(Catalog::seed(fn () => Promotion::query()->whereKey($coupon->promotion_id)->exists()))->toBeFalse();
});

it('ví voucher (API): tính thử từng mã trên giỏ, mã giảm nhiều nhất là best, mã chưa đủ đơn tối thiểu có gợi ý mua thêm', function () {
    manageCoupons($this);
    $this->post($this->base, couponPayload(['code' => 'GIAM10']))->assertSessionHasNoErrors();
    $this->post($this->base, couponPayload(['code' => 'GIAM50K', 'discount_type' => 'amount', 'discount_value' => 50_000, 'max_discount' => null]))->assertSessionHasNoErrors();
    $this->post($this->base, couponPayload(['code' => 'DON1TR', 'discount_type' => 'amount', 'discount_value' => 100_000, 'min_subtotal' => 1_000_000]))->assertSessionHasNoErrors();
    $this->post($this->base, couponPayload(['code' => 'ANMA', 'public' => false]))->assertSessionHasNoErrors();
    [$cart, $headers] = apiCart($this, $this->s->id); // 300.000đ

    StorefrontApiContract::assertRoutesDocumented(dirname(__DIR__, 2));
    $response = StorefrontApiContract::assertResponse($this->getJson("/api/storefront/v1/x/vani-coupon-code/carts/{$cart}/wallet", $headers), dirname(__DIR__, 2), '/carts/{cart}/wallet', 'GET')
        ->assertOk();

    expect(collect($response->json('data'))->map(fn (array $item) => [$item['code'], $item['applicable'], $item['discount']['amount']])->all())->toBe([
        ['GIAM50K', true, 50_000],
        ['GIAM10', true, 20_000], // 10% = 30.000 → tối đa 20.000
        ['DON1TR', false, 0],
    ])->and($response->json('meta.best'))->toBe('GIAM50K')
        ->and($response->json('data.2.message'))->toContain('Mua thêm')
        ->and($response->json('data.2.message'))->toContain('700.000');

    $this->getJson("/api/storefront/v1/x/vani-coupon-code/carts/{$cart}/wallet", ['X-Vani-Cart-Token' => 'sai'])->assertNotFound();
});

it('trang giỏ hiện ví voucher; trang sản phẩm chỉ hiện mã có phạm vi chứa sản phẩm', function () {
    manageCoupons($this);
    $this->post($this->base, couponPayload(['code' => 'DAM10', 'scope_type' => 'products', 'scope_values' => [$this->s->product_id]]))->assertSessionHasNoErrors();
    $this->post($this->base, couponPayload(['code' => 'TUI10', 'scope_type' => 'products', 'scope_values' => [$this->bag->product_id]]))->assertSessionHasNoErrors();
    auth('staff')->logout();

    $this->post(route('storefront.cart.add'), ['variant_id' => $this->s->id, 'quantity' => 1])->assertRedirect();
    $this->get(route('storefront.cart'))->assertOk()
        ->assertSee('data-vani-coupon-wallet="cart"', false)
        ->assertSee('data-vani-coupon-use="DAM10"', false)
        ->assertDontSee('data-vani-coupon-use="TUI10"', false);

    $this->get(route('storefront.product', 'tui-da'))->assertOk()->assertSee('data-vani-coupon-copy="TUI10"', false)->assertDontSee('DAM10');
    StorefrontApiContract::assertResponse($this->getJson('/api/storefront/v1/x/vani-coupon-code/products/'.$this->bag->product_id), dirname(__DIR__, 2), '/products/{product}', 'GET')
        ->assertJsonPath('data.0.code', 'TUI10')->assertJsonCount(1, 'data');
});

it('mã mẫu: phát mã riêng cho khách chọn (không phát trùng), cả nhóm khách chạy nền; khách thấy mã ở tài khoản và chỉ khách đó dùng được', function () {
    $lan = Customers::account('0912000001');
    $mai = Customers::account('0912000002');
    $vip = Catalog::seed(fn () => CustomerGroup::query()->create(['code' => 'vip', 'name' => 'VIP']));
    app(CustomerSegments::class)->assignGroup($mai->id, $vip->id, 'test');
    manageCoupons($this);
    $this->post($this->base, couponPayload(['kind' => 'template', 'code' => null, 'prefix' => 'chao', 'name' => 'Chào mừng', 'valid_days' => 30, 'public' => false]))->assertSessionHasNoErrors();
    $coupon = Coupon::query()->sole();

    $this->post("{$this->base}/{$coupon->id}/issue", ['target' => 'customers', 'customer_ids' => [$lan->id]])->assertSessionHasNoErrors();
    $this->post("{$this->base}/{$coupon->id}/issue", ['target' => 'customers', 'customer_ids' => [$lan->id]])->assertSessionHasNoErrors();
    Queue::fake();
    $this->post("{$this->base}/{$coupon->id}/issue", ['target' => 'segment', 'group_id' => $vip->id])->assertSessionHasNoErrors();
    Queue::assertPushed(IssueCouponCodes::class, fn (IssueCouponCodes $job): bool => $job->couponId === $coupon->id && $job->groupId === $vip->id);
    app()->call([new IssueCouponCodes($coupon->id, $vip->id, null), 'handle']);

    $vouchers = app(Vouchers::class)->ofPromotion((int) $coupon->promotion_id);
    $lanCode = collect($vouchers)->firstWhere('customerId', $lan->id);
    expect(collect($vouchers)->pluck('customerId')->sort()->values()->all())->toBe([$lan->id, $mai->id])
        ->and($lanCode->code)->toStartWith('CHAO')
        ->and($lanCode->usageLimit)->toBe(1)
        ->and($lanCode->expiresAt)->toBeGreaterThan(now()->addDays(29)->getTimestamp());

    auth('staff')->logout();
    Customers::webLogin($this, '0912000001');
    $this->get('/tai-khoan/ma-giam-gia')->assertOk()->assertSee($lanCode->code);
    StorefrontApiContract::assertResponse($this->getJson('/api/storefront/v1/x/vani-coupon-code/mine', ['Sec-Fetch-Site' => 'same-origin']), dirname(__DIR__, 2), '/mine', 'GET')
        ->assertOk()->assertJsonPath('data.usable.0.code', $lanCode->code);
    $this->post(route('storefront.cart.add'), ['variant_id' => $this->s->id, 'quantity' => 1])->assertRedirect();
    $this->get(route('storefront.cart'))->assertSee('data-vani-coupon-use="'.$lanCode->code.'"', false);
});
