<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\HelloWorld\HelloWorldServiceProvider;

/**
 * Plugin thật, cài/bật qua PluginManager rồi nạp provider như lúc boot.
 */
function installHelloWorld(string $scopeType = 'owner', ?int $scopeId = null): void
{
    $context = app(CurrentContext::class);
    $context->runAs(ContextScope::system('test'), function () use ($scopeType, $scopeId) {
        $plugins = app(PluginManager::class);
        $plugins->install('vani.hello-world');
        $plugins->enable('vani.hello-world', $scopeType, $scopeId);
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

it('chỉ hoạt động với nhân viên thuộc brand được bật', function () {
    [$lumiere, $urbanx] = Brand::factory()->count(2)->create();
    installHelloWorld('brand', $lumiere->id);

    $inScope = StaffUser::factory()->withPermissions(['admin.access', 'hello-world.view'], ScopeType::Brand, $lumiere->id)->create();
    $outOfScope = StaffUser::factory()->withPermissions(['admin.access', 'hello-world.view'], ScopeType::Brand, $urbanx->id)->create();

    $this->actingAs($inScope, 'staff')->get('/admin/plugins/vani-hello-world')->assertOk();
    $this->actingAs($outOfScope, 'staff')->get('/admin/plugins/vani-hello-world')->assertNotFound();
    $this->actingAs($outOfScope, 'staff')->get('/admin')->assertInertia(fn (Assert $page) => $page->where('cards', []));
});
