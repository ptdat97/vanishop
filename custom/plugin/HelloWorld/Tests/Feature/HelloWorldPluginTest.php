<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Tenancy\Contracts\Settings;
use Plugin\HelloWorld\HelloWorldServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/**
 * Plugin thật, cài/bật qua PluginManager rồi nạp provider như lúc boot.
 */
function installHelloWorld(): void
{
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        $plugins = app(PluginManager::class);
        $plugins->install('vani.hello-world');
        $plugins->enable('vani.hello-world');
    });

    app()->register(HelloWorldServiceProvider::class);
}

it('thêm card, menu và trang Admin khi được bật', function () {
    installHelloWorld();
    $staff = StaffUser::factory()->withPermissions(['admin.access', 'hello-world.view'])->create();

    $this->actingAs($staff, 'staff')->get('/admin')
        ->assertInertia(fn (Assert $page) => $page
            ->where('cards.0.title', 'Hello World')
            ->where('navigation', fn ($items) => collect($items)->pluck('key')->contains('hello-world')));

    $this->actingAs($staff, 'staff')->get('/admin/plugins/vani-hello-world')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('HelloWorld::Index'));
});

it('ẩn menu và chặn trang khi nhân viên thiếu quyền', function () {
    installHelloWorld();
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin')
        ->assertInertia(fn (Assert $page) => $page->where('navigation', fn ($items) => ! collect($items)->pluck('key')->contains('hello-world')));

    $this->actingAs($staff, 'staff')->get('/admin/plugins/vani-hello-world')->assertForbidden();
});

it('plugin bị tắt → trang trả 404, không còn card', function () {
    installHelloWorld();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PluginManager::class)->disable('vani.hello-world'));
    app(PluginActivation::class)->flush();
    $staff = StaffUser::factory()->withPermissions(['admin.access', 'hello-world.view'])->create();

    $this->actingAs($staff, 'staff')->get('/admin/plugins/vani-hello-world')->assertNotFound();
    $this->actingAs($staff, 'staff')->get('/admin')->assertInertia(fn (Assert $page) => $page->where('cards', []));
});

it('storefront: lời chào trong dữ liệu PDP (API + native) và hiện qua slot', function () {
    installHelloWorld();
    ['s' => $variant] = CheckoutTestHelpers::store();
    $slug = $variant->style->slug;

    $extensions = $this->getJson("/api/storefront/v1/products/{$slug}")->assertOk()->json('data.extensions');
    expect($extensions)->toBe(['vani.hello-world' => ['greeting' => 'Xin chào từ Đầm lụa!']]);
    $this->get("/san-pham/{$slug}")->assertOk()->assertSee('Xin chào từ Đầm lụa!');
});

it('Admin: phần form sản phẩm lưu vào bảng riêng, cột + bộ lọc danh sách, thao tác trên đơn, tab khách', function () {
    installHelloWorld();
    ['s' => $variant] = CheckoutTestHelpers::store();
    $style = $variant->style;
    $staff = StaffUser::factory()->withPermissions(['admin.access', 'catalog.view', 'catalog.manage', 'orders.view', 'customers.view', 'hello-world.view'])->create();
    $this->actingAs($staff, 'staff');

    $this->get("/admin/catalog/products/{$style->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('extensionSections.0.plugin', 'vani.hello-world')
        ->where('extensionSections.0.fields.0.key', 'note'));

    $payload = fn (string $note) => [
        'style_code' => $style->style_code, 'slug' => $style->slug, 'status' => 'active', 'lock_version' => $style->fresh()->lock_version,
        'translations' => ['vi' => ['name' => 'Đầm lụa']], 'category_ids' => [], 'brand_id' => $style->brand_id,
        'extensions' => ['vani-hello-world' => ['note' => ['note' => $note]]],
    ];
    $this->put("/admin/catalog/products/{$style->id}", $payload(str_repeat('x', 300)))->assertSessionHasErrors('extensions.vani-hello-world.note.note');
    $this->put("/admin/catalog/products/{$style->id}", $payload('Hàng mới về'))->assertSessionHasNoErrors();
    expect(DB::table('plg_hello_world_notes')->where('style_id', $style->id)->value('note'))->toBe('Hàng mới về');

    $this->get('/admin/catalog/products?ext[vani.hello-world:has_note]=yes')->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where("extensions.values.{$style->id}", ['vani.hello-world:note' => 'Hàng mới về'])
        ->where('extensions.filters.0.key', 'vani.hello-world:has_note'));

    $response = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $response->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$response->json('data.id')}/lines", ['variant_id' => $variant->id, 'quantity' => 1], $headers)->assertOk();
    $this->postJson("/api/storefront/v1/checkout/{$response->json('data.id')}/orders", CheckoutTestHelpers::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'hello-order-1'])->assertCreated();
    $order = Order::query()->withoutGlobalScopes()->sole();

    $this->get("/admin/orders/orders/{$order->id}")->assertInertia(fn (Assert $page) => $page->where('extensions.actions.0.key', 'vani.hello-world:greet'));
    $this->from("/admin/orders/orders/{$order->id}")->post('/admin/extensions/order/actions/vani.hello-world/greet', ['ids' => [$order->id]])
        ->assertRedirect("/admin/orders/orders/{$order->id}")->assertSessionHas('success', "Đã gửi lời chào cho đơn #{$order->id}.");

    $customer = Customer::query()->sole();
    $this->get("/admin/customers/{$customer->public_id}")->assertInertia(fn (Assert $page) => $page
        ->where('extensions.tabs.0.rows.0.value', "Xin chào khách #{$customer->id}"));
});

it('storefront: API và trang riêng của plugin; theme override được view plugin; plugin tắt → 404', function () {
    installHelloWorld();

    $this->getJson('/api/storefront/v1/x/vani-hello-world/greeting?name=Lan')->assertOk()->assertJsonPath('data.message', 'Xin chào, Lan!');
    $this->get('/p/vani-hello-world')->assertOk()->assertSee('Trang này do plugin vani.hello-world')->assertSee('<header', false);

    $themes = storage_path('framework/testing/themes-'.uniqid());
    File::ensureDirectoryExists("{$themes}/shop/plugins/vani-hello-world/pages");
    File::link(base_path('custom/theme/vani-base'), "{$themes}/vani-base");
    File::put("{$themes}/shop/theme.json", json_encode(['parent' => 'vani-base']));
    File::put("{$themes}/shop/plugins/vani-hello-world/pages/hello.blade.php", "@extends('theme::layouts.app')\n@section('content')Bản theme của trang Hello @endsection");
    config(['vanishop.storefront.themes_path' => $themes]);
    app()->forgetInstance(Themes::class);
    app(Settings::class)->set('core', 'theme', 'shop');
    $this->get('/p/vani-hello-world')->assertOk()->assertSee('Bản theme của trang Hello');
    File::deleteDirectory($themes);

    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PluginManager::class)->disable('vani.hello-world'));
    app(PluginActivation::class)->flush();
    $this->getJson('/api/storefront/v1/x/vani-hello-world/greeting')->assertNotFound();
    $this->get('/p/vani-hello-world')->assertNotFound();
});

it('giao dịch: lời chúc gói quà trên form thêm giỏ (native) → giỏ → dòng đơn; quá dài bị từ chối', function () {
    installHelloWorld();
    ['s' => $variant] = CheckoutTestHelpers::store();

    $this->get("/san-pham/{$variant->style->slug}")->assertSee('name="options[vani.hello-world][message]"', false);
    $this->post('/gio-hang', ['variant_id' => $variant->id, 'options' => ['vani.hello-world' => ['message' => str_repeat('a', 61)]]])->assertSessionHasErrors('business');
    $this->post('/gio-hang', ['variant_id' => $variant->id, 'options' => ['vani.hello-world' => ['message' => 'Chúc mừng sinh nhật!']]])->assertRedirect('/gio-hang');
    $this->get('/gio-hang')->assertSee('Chúc mừng sinh nhật!');

    $this->post('/thanh-toan', [
        'contact' => ['full_name' => 'Lan', 'phone' => '0912345678'],
        'shipping_address' => ['province_name' => 'Hà Nội', 'ward_name' => 'Hoàn Kiếm', 'street_line' => '1 Tràng Tiền'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => 330_000, 'idempotency_key' => 'gift-order-0001',
    ]);
    $order = Order::query()->withoutGlobalScopes()->sole();
    expect($order->lines()->sole()->meta)->toBe(['options' => ['vani.hello-world' => ['message' => 'Chúc mừng sinh nhật!']]]);
    $this->get("/don-hang/{$order->public_id}")->assertSee('Chúc mừng sinh nhật!');
});
