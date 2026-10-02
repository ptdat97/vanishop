<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\HelloWorld\HelloWorldServiceProvider;

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
