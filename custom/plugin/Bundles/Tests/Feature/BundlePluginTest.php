<?php

use Database\Seeders\Demo\BundlesSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as Checkout;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\StorefrontApiContract;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as Inventory;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as Pricing;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Shared\Support\StoreClock;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Bundles\Application\Bundles;
use Plugin\Bundles\BundlesServiceProvider;
use Plugin\Bundles\Infrastructure\BundlePageSurface;
use Plugin\Bundles\Persistence\Bundle;

require_once dirname(__DIR__, 5).'/modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    Catalog::seed(function () {
        $plugins = app(PluginManager::class);
        $plugins->install(BundlesServiceProvider::ID);
        $plugins->enable(BundlesServiceProvider::ID);
    });
    app()->register(BundlesServiceProvider::class);
    app(PluginActivation::class)->flush();
});

function bundleProducts(): array
{
    ['s' => $shirt, 'm' => $lowStockShirt, 'location' => $warehouse] = Checkout::store();
    Inventory::stock($warehouse, $lowStockShirt->id, 1);

    $skirtStyle = Catalog::product(null, ['name' => 'Chân váy', 'slug' => 'chan-vay']);
    [$skirt, $unpricedSkirt] = Pricing::variants($skirtStyle);
    Inventory::stock($warehouse, $skirt->id, 10);
    Inventory::stock($warehouse, $unpricedSkirt->id, 10);
    Pricing::priceList([], [$skirt->id => [150_000]]);

    return [$shirt, $lowStockShirt, $skirt, $unpricedSkirt];
}

function bundlePayload(int $firstStyle, int $secondStyle): array
{
    return [
        'name' => 'Bộ công sở', 'slug' => 'bo-cong-so', 'description' => 'Hai áo và một chân váy.',
        'discount_percent' => 10, 'active' => true,
        'items' => [['style_id' => $firstStyle, 'quantity' => 2], ['style_id' => $secondStyle, 'quantity' => 1]],
    ];
}

function manageBundles($test): void
{
    $test->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'bundles.view', 'bundles.manage', 'media.view', 'promotion.view', 'storefront.manage'])->create(), 'staff');
}

it('seed từng combo mẫu đủ sản phẩm và tạo khuyến mãi đúng một lần', function () {
    $shirt = Catalog::product(null, ['style_code' => 'VS-DSC6826']);
    $skirt = Catalog::product(null, ['style_code' => 'VS-DSC7406']);
    Catalog::product(null, ['style_code' => 'VS-DTT9540']);
    Catalog::product(null, ['style_code' => 'VS-VNQ9889']);

    $this->seed(BundlesSeeder::class);
    $office = Bundle::query()->sole();
    expect($office->slug)->toBe('set-cong-so');
    expect($office->requirement())->toBe([$shirt->id => 1, $skirt->id => 1]);

    Catalog::product(null, ['style_code' => 'VS-VNQ7102']);
    $this->seed(BundlesSeeder::class);
    $this->assertDatabaseCount('plg_bundles', 2);
    $this->assertModelExists($office);
    expect(app(ManagedPromotions::class)->ids(BundlesServiceProvider::ID))->toHaveCount(2);
});

it('lưu, sửa và xoá combo cùng khuyến mãi được quản lý trong Admin', function () {
    [$first, , $second] = bundleProducts();
    manageBundles($this);
    $payload = bundlePayload($first->style_id, $second->style_id);

    $this->getJson(route('admin.plugins.vani-bundles.products', ['q' => 'Chân váy']))
        ->assertJsonPath('data.0.value', (string) $second->style_id);
    $this->post(route('admin.plugins.vani-bundles.store'), [
        ...$payload, 'ends_at' => '2030-01-01T12:00',
    ])->assertSessionHasNoErrors();
    $bundle = Bundle::query()->sole();
    $promotion = Promotion::query()->where('managed_by', BundlesServiceProvider::ID)->sole();
    expect($promotion->action_config)->toBe(['items' => [$first->style_id => 2, $second->style_id => 1], 'basis_points' => 1000]);

    $this->get(route('admin.plugins.vani-bundles.index'))->assertInertia(fn (Assert $page) => $page->component('Bundles::Index')
        ->where('navigation', fn ($items) => collect($items)->pluck('group', 'key')->get('bundles') === 'catalog')
        ->where('urls.media', route('admin.media.index'))
        ->where('bundleUrls.create', route('admin.plugins.vani-bundles.create'))
        ->where('bundleUrls.storefront', route('storefront.p.vani-bundles.index'))
        ->where('bundleUrls.builder', route('admin.storefront.builder.edit', BundlePageSurface::KEY))
        ->has('bundles.0.period', 1)
        ->where('bundles.0.promotion_url', route('admin.promotion.promotions.edit', $promotion)));
    $this->get(route('admin.plugins.vani-bundles.edit', $bundle))->assertInertia(fn (Assert $page) => $page->component('Bundles::Form')
        ->where('urls.media', route('admin.media.index'))
        ->where('bundleUrls.save', route('admin.plugins.vani-bundles.update', $bundle))
        ->where('bundle.items.0.quantity', 2)
        ->where('bundle.ends_at', '2030-01-01T12:00'));
    $this->get(route('admin.promotion.promotions.edit', $promotion))->assertInertia(fn (Assert $page) => $page
        ->where('promotion.managed.url', route('admin.plugins.vani-bundles.edit', $bundle))
        ->where('promotion.action_config.basis_points', 1000));

    $this->put(route('admin.plugins.vani-bundles.update', $bundle), [
        ...$payload, 'discount_percent' => 20, 'active' => false, 'items' => array_reverse($payload['items']),
    ])->assertSessionHasNoErrors();
    expect($promotion->fresh()->action_config['basis_points'])->toBe(2000);
    expect($promotion->fresh()->status)->toBe('inactive');
    expect($bundle->items()->first()->style_id)->toBe($second->style_id);

    $this->delete(route('admin.plugins.vani-bundles.destroy', $bundle))->assertRedirect(route('admin.plugins.vani-bundles.index'));
    $this->assertModelMissing($bundle);
    $this->assertModelMissing($promotion);
});

it('giữ nguyên dữ liệu khi lịch, ảnh hoặc sản phẩm trong combo không hợp lệ', function () {
    [$first, , $second] = bundleProducts();
    manageBundles($this);
    $payload = bundlePayload($first->style_id, $second->style_id);
    $url = route('admin.plugins.vani-bundles.store');

    $this->post($url, [...$payload, 'starts_at' => '2030-01-01T12:00', 'ends_at' => '2030-01-01T12:00'])
        ->assertSessionHasErrors('ends_at');
    $this->post($url, [...$payload, 'image_media_id' => 999999])->assertSessionHasErrors('image_media_id');
    $this->post($url, [...$payload, 'items' => [$payload['items'][0], ['style_id' => 999999, 'quantity' => 1]]])
        ->assertSessionHasErrors('items.1.style_id');
    $this->assertDatabaseCount('plg_bundles', 0);
    expect(app(ManagedPromotions::class)->ids(BundlesServiceProvider::ID))->toBe([]);
});

it('chặn lưu và tìm sản phẩm khi nhân viên chỉ có quyền xem combo', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'bundles.view'])->create(), 'staff');

    $this->get(route('admin.plugins.vani-bundles.index'))->assertInertia(fn (Assert $page) => $page
        ->where('can.manage', false)->where('urls.media', null)->where('bundleUrls.builder', null));
    $this->getJson(route('admin.plugins.vani-bundles.products'))->assertForbidden();
    $this->post(route('admin.plugins.vani-bundles.store'))->assertForbidden();
});

it('trang Combo dùng Builder để bố trí nội dung trước và sau danh sách đang chạy', function () {
    [$shirt, , $skirt] = bundleProducts();
    manageBundles($this);
    $this->get('/en/bundles')->assertOk()->assertSee('Product bundles')->assertSee('There are no bundles on this page.');

    $payload = bundlePayload($shirt->style_id, $skirt->style_id);
    $store = route('admin.plugins.vani-bundles.store');
    $this->post($store, $payload)->assertSessionHasNoErrors();
    $this->post($store, [...$payload, 'slug' => 'combo-tat', 'name' => 'Combo đã tắt', 'active' => false])->assertSessionHasNoErrors();
    $this->post($store, [...$payload, 'slug' => 'combo-tuong-lai', 'name' => 'Combo tương lai', 'starts_at' => '2030-01-01T12:00'])->assertSessionHasNoErrors();

    $builder = route('admin.storefront.builder.edit', BundlePageSurface::KEY);
    $this->get($builder)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/Builder')
        ->where('surface.key', BundlePageSurface::KEY)
        ->where('zones.0.slot', BundlePageSurface::BEFORE_LIST)
        ->where('zones.1.slot', BundlePageSurface::AFTER_LIST));
    $this->put($builder, ['blocks' => [
        ['type' => 'rich_text', 'zone' => BundlePageSurface::BEFORE_LIST, 'config' => ['body' => '<p>Phối đồ trong tuần</p>']],
        ['type' => 'rich_text', 'zone' => BundlePageSurface::AFTER_LIST, 'config' => ['body' => '<p>Hướng dẫn chọn size</p>']],
    ]])->assertSessionHasNoErrors();

    $this->get('/combo')->assertOk()
        ->assertSeeInOrder(['Phối đồ trong tuần', 'data-vani-bundle-form', 'Hướng dẫn chọn size'], false)
        ->assertSee('Bộ công sở')->assertDontSee('Combo đã tắt')->assertDontSee('Combo tương lai');
    $pluginPath = dirname(__DIR__, 2);
    StorefrontApiContract::assertRoutesDocumented($pluginPath);
    StorefrontApiContract::assertResponse($this->getJson('/api/storefront/v1/x/vani-bundles/bundles'), $pluginPath, '/bundles', 'GET')
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Bộ công sở');
    $this->get(route('storefront.product', $shirt->style->slug))->assertSee(route('storefront.p.vani-bundles.index'), false);
});

it('phân trang combo theo thứ tự đã lưu', function () {
    [$shirt, , $skirt] = bundleProducts();
    $payload = bundlePayload($shirt->style_id, $skirt->style_id);
    Catalog::seed(function () use ($payload): void {
        $bundles = app(Bundles::class);
        foreach (range(9, 1) as $position) {
            $bundles->save([
                ...$payload, 'slug' => 'set-'.$position, 'name' => "Bộ phối số {$position}",
                'image_media_id' => null, 'position' => $position, 'starts_at' => null, 'ends_at' => null,
            ]);
        }
    });

    $this->get('/combo')->assertOk()->assertSeeInOrder(['Bộ phối số 1', 'Bộ phối số 8'])
        ->assertDontSee('Bộ phối số 9')->assertSee('rel="next"', false);
    $this->get('/combo?page=2')->assertOk()->assertSee('Bộ phối số 9')
        ->assertDontSee('Bộ phối số 8')->assertSee('rel="prev"', false)->assertDontSee('rel="next"', false);
});

it('hiện combo đang chạy trên PDP và Builder với giá từ SKU đủ hàng', function () {
    [$shirt, $lowStockShirt, $skirt, $unpricedSkirt] = bundleProducts();
    manageBundles($this);
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(8, 0));
    $payload = bundlePayload($shirt->style_id, $skirt->style_id);
    $this->post(route('admin.plugins.vani-bundles.store'), $payload)->assertSessionHasNoErrors();
    $bundle = Bundle::query()->sole();
    for ($position = 1; $position <= 6; $position++) {
        $this->post(route('admin.plugins.vani-bundles.store'), [
            ...$payload, 'slug' => 'sap-chay-'.$position, 'name' => 'Combo sắp chạy', 'position' => $position,
            'starts_at' => '2030-01-01T12:00',
        ])->assertSessionHasNoErrors();
    }
    $bundle->update(['position' => 7]);

    $this->put(route('admin.storefront.builder.edit', 'product'), ['blocks' => [[
        'type' => 'bundle', 'zone' => 'vani.storefront.pdp.after_related_products',
        'config' => ['bundle' => (string) $bundle->id, 'title' => 'Phối đồ'],
    ]]])->assertSessionHasNoErrors();

    $pdp = route('storefront.product', $shirt->style->slug);
    $response = $this->get($pdp)->assertOk()
        ->assertSee('data-vani-bundles', false)
        ->assertSee('data-block="bundle"', false)
        ->assertSee('data-vani-bundle-total data-vani-amount="675000"', false)
        ->assertSee('name="items[0][quantity]" value="2"', false)
        ->assertDontSee('Combo sắp chạy');
    $html = $response->getContent();
    expect($html)->toMatch('/<option value="'.$lowStockShirt->id.'"\s+disabled/')
        ->and($html)->not->toContain('<option value="'.$unpricedSkirt->id.'"');
    expect(substr_count($html, 'data-vani-bundle-form'))->toBe(2);
    expect(substr_count($html, vani_asset(BundlesServiceProvider::ID, 'bundles.js')))->toBe(1);

    Catalog::seed(fn () => app(Settings::class)->set('core', 'storefront.price_visibility', 'members'));
    $this->get($pdp)->assertDontSee('data-vani-bundle-total', false);
    Catalog::seed(fn () => $skirt->style->update(['status' => 'archived']));
    $this->get($pdp)->assertDontSee('data-vani-bundle-form', false);
});

it('chỉ giảm giá cho phần mua đủ bộ từ nhiều SKU và ghi nhận mức giảm trên đơn', function () {
    config(['vani.shipping-flat-rate.fee' => 30_000, 'vani.shipping-flat-rate.free_over' => null]);
    [$shirt, $lowStockShirt, $skirt] = bundleProducts();
    manageBundles($this);
    $this->post(route('admin.plugins.vani-bundles.store'), bundlePayload($shirt->style_id, $skirt->style_id))->assertSessionHasNoErrors();

    $cart = route('storefront.cart.add');
    $this->post($cart, ['items' => [
        ['variant_id' => $shirt->id, 'quantity' => 2], ['variant_id' => $lowStockShirt->id, 'quantity' => 1],
    ]])->assertRedirect(route('storefront.cart'));
    $this->get(route('storefront.checkout'))->assertViewHas('quote', fn (array $quote): bool => $quote['discount']['amount'] === 0);
    $added = $this->postJson($cart, ['variant_id' => $skirt->id])->assertOk()
        ->assertJsonPath('count', 4)->assertJsonPath('voucher', null);
    expect($added->json('html'))->toContain('−65.000 ₫');
    $this->get(route('storefront.cart'))->assertSee('−65.000 ₫');
    $this->postJson(route('storefront.cart.select'), ['lines' => [
        $added->json('cart.lines.0.id'), $added->json('cart.lines.1.id'),
    ]])->assertOk();
    $this->get(route('storefront.cart'))->assertDontSee('−65.000 ₫');
    $this->postJson(route('storefront.cart.select'), ['lines' => array_column($added->json('cart.lines'), 'id')])->assertOk();
    $this->get(route('storefront.checkout'))->assertViewHas('quote', fn (array $quote): bool => $quote['discount']['amount'] === 65_000);

    $this->post(route('storefront.checkout.store'), Checkout::orderPayload([
        'expected_total' => 915_000, 'idempotency_key' => 'bundle-checkout-01',
    ]))->assertSessionHasNoErrors();
    $order = Order::query()->withoutGlobalScopes()->sole();
    expect($order->discount_amount)->toBe(65_000);
    $this->assertDatabaseHas('promotion_usages', ['order_id' => $order->id, 'discount_amount' => 65_000]);
});

it('danh sách combo và ưu đãi trên giỏ cùng bắt đầu và kết thúc đúng giờ đã lưu', function () {
    $this->freezeSecond();
    [$shirt, , $skirt] = bundleProducts();
    manageBundles($this);
    $startsAt = now()->addMinutes(2)->startOfMinute();
    $endsAt = $startsAt->copy()->addMinutes(10);
    $this->post(route('admin.plugins.vani-bundles.store'), [
        ...bundlePayload($shirt->style_id, $skirt->style_id),
        'starts_at' => StoreClock::toInput($startsAt), 'ends_at' => StoreClock::toInput($endsAt),
    ])->assertSessionHasNoErrors();
    $this->postJson(route('storefront.cart.add'), ['items' => [
        ['variant_id' => $shirt->id, 'quantity' => 2], ['variant_id' => $skirt->id, 'quantity' => 1],
    ]])->assertOk();

    $api = '/api/storefront/v1/x/vani-bundles/bundles';
    $this->getJson($api)->assertOk()->assertJsonCount(0, 'data');
    $this->get(route('storefront.checkout'))->assertViewHas('quote', fn (array $quote): bool => $quote['discount']['amount'] === 0);

    $this->travelTo($startsAt);
    $this->getJson($api)->assertOk()->assertJsonPath('data.0.slug', 'bo-cong-so');
    $this->get(route('storefront.checkout'))->assertViewHas('quote', fn (array $quote): bool => $quote['discount']['amount'] === 75_000);

    $this->travelTo($endsAt);
    $this->getJson($api)->assertOk()->assertJsonCount(0, 'data');
    $this->get(route('storefront.checkout'))->assertViewHas('quote', fn (array $quote): bool => $quote['discount']['amount'] === 0);
});

it('khi khách bớt món, tính lại ưu đãi theo số lượng của combo lúc đặt đơn', function () {
    Checkout::autoConfirmCod();
    config(['vani.shipping-flat-rate.fee' => 0]);
    [$shirt, , $skirt] = bundleProducts();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'bundles.manage', 'orders.manage', 'orders.cancel'])->create(), 'staff');
    $payload = bundlePayload($shirt->style_id, $skirt->style_id);
    $this->post(route('admin.plugins.vani-bundles.store'), $payload)->assertSessionHasNoErrors();
    $bundle = Bundle::query()->sole();
    $promotionId = app(ManagedPromotions::class)->ids(BundlesServiceProvider::ID)[$bundle->id];

    $this->postJson(route('storefront.cart.add'), ['items' => [
        ['variant_id' => $shirt->id, 'quantity' => 2], ['variant_id' => $skirt->id, 'quantity' => 1],
    ]])->assertOk();
    $this->post(route('storefront.checkout.store'), Checkout::orderPayload([
        'expected_total' => 675_000, 'idempotency_key' => 'bundle-clawback-01',
    ]))->assertSessionHasNoErrors();
    $order = Order::query()->withoutGlobalScopes()->sole();

    $payload['items'][0]['quantity'] = 1;
    $this->put(route('admin.plugins.vani-bundles.update', $bundle), $payload)->assertSessionHasNoErrors();
    expect(Promotion::query()->findOrFail($promotionId)->action_config['items'][$shirt->style_id])->toBe(1);
    $this->assertDatabaseHas('promotion_usages', ['promotion_id' => $promotionId, 'order_id' => $order->id, 'discount_amount' => 75_000]);

    $shirtLine = $order->lines()->where('variant_id', $shirt->id)->sole();
    $this->post("/admin/orders/orders/{$order->id}/cancel-lines", [
        'lines' => [$shirtLine->id => 1], 'cause' => 'customer', 'reason' => 'Khách bớt một món', 'lock_version' => $order->lock_version,
    ])->assertSessionHasNoErrors();

    expect($order->fresh())->discount_amount->toBe(0)->total_amount->toBe(450_000);
    $this->assertDatabaseHas('promotion_usages', ['promotion_id' => $promotionId, 'order_id' => $order->id, 'discount_amount' => 0]);
});
