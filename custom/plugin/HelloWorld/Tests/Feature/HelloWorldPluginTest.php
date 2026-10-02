<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
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
