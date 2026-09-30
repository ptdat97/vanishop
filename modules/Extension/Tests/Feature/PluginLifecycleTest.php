<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;
use Modules\Extension\Application\Plugins\PluginStateCache;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookType;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Facades\Hook;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Extension\Tests\Fixtures\GreetingPluginProvider;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

beforeEach(function () {
    $this->root = FixturePlugins::install([
        'Greeting' => ['id' => 'fixture.greeting'],
        'Base' => ['id' => 'fixture.base'],
        'Child' => ['id' => 'fixture.child', 'requires' => ['vanishop' => '^0.2', 'plugins' => ['fixture.base' => '^1.0']]],
        'Rival' => ['id' => 'fixture.rival', 'conflicts' => ['fixture.greeting']],
        'Future' => ['id' => 'fixture.future', 'requires' => ['vanishop' => '^9.0']],
    ]);
    $this->plugins = app(PluginManager::class);
    app(CurrentContext::class)->set(ContextScope::system('test'));
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

it('cài plugin, ghi audit và cache nạp theo thứ tự phụ thuộc', function () {
    $this->plugins->install('fixture.base');
    $this->plugins->install('fixture.greeting');

    expect(PluginRecord::query()->find('fixture.base')->status)->toBe(PluginStatus::Installed)
        ->and(AuditLog::query()->where('action', 'extension.plugin.installed')->count())->toBe(2)
        ->and(array_column(app(PluginStateCache::class)->read(), 'id'))->toBe(['fixture.base', 'fixture.greeting']);
});

it('từ chối cài khi Core không tương thích, thiếu phụ thuộc hoặc đã cài', function () {
    expect(fn () => $this->plugins->install('fixture.future'))->toThrow(PluginOperationFailed::class, 'incompatible_core')
        ->and(fn () => $this->plugins->install('fixture.child'))->toThrow(PluginOperationFailed::class, 'inactive_dependency');

    $this->plugins->install('fixture.base');
    expect(fn () => $this->plugins->install('fixture.base'))->toThrow(PluginOperationFailed::class, 'đã được cài');
});

it('từ chối bật plugin xung đột với plugin đang bật', function () {
    $this->plugins->install('fixture.greeting');
    $this->plugins->install('fixture.rival');
    $this->plugins->enable('fixture.greeting');

    $this->plugins->enable('fixture.rival');
})->throws(PluginOperationFailed::class, 'conflict');

it('chạy migration khi cài và rollback khi gỡ với --purge', function () {
    FixturePlugins::withMigration($this->root, 'Base');

    $this->plugins->install('fixture.base');
    expect(Schema::hasTable('plg_fixture_items'))->toBeTrue();

    $this->plugins->uninstall('fixture.base', purge: true);
    expect(Schema::hasTable('plg_fixture_items'))->toBeFalse()
        ->and(PluginRecord::query()->find('fixture.base'))->toBeNull();
});

it('listener của plugin chỉ chạy trong phạm vi được bật', function () {
    app(HookRegistry::class)->declare(new HookDefinition('test.greeting', HookType::Filter, true, '0.1'));
    [$lumiere, $urbanx] = Brand::factory()->count(2)->create();

    $this->plugins->install('fixture.greeting');
    $this->app->register(GreetingPluginProvider::class);
    $this->plugins->enable('fixture.greeting', 'brand', $lumiere->id);

    $context = app(CurrentContext::class);
    $greet = fn (array $brands) => $context->runAs(new ContextScope(Actor::guest(), brandIds: $brands), function () {
        app(PluginActivation::class)->flush();

        return Hook::filter('test.greeting', 'hi');
    });

    expect($greet([$lumiere->id]))->toBe('hi + fixture.greeting')
        ->and($greet([$urbanx->id]))->toBe('hi');

    $this->plugins->disable('fixture.greeting');
    expect($greet([$lumiere->id]))->toBe('hi')
        ->and(PluginRecord::query()->find('fixture.greeting')->status)->toBe(PluginStatus::Disabled);
});

it('không cho tắt hoặc gỡ plugin đang được plugin khác phụ thuộc', function () {
    $this->plugins->install('fixture.base');
    $this->plugins->enable('fixture.base');
    $this->plugins->install('fixture.child');
    $this->plugins->enable('fixture.child');

    expect(fn () => $this->plugins->disable('fixture.base'))->toThrow(PluginOperationFailed::class, 'fixture.child')
        ->and(fn () => $this->plugins->uninstall('fixture.child'))->toThrow(PluginOperationFailed::class, 'tắt');
});

it('kiểm tra scope hợp lệ khi bật', function (string $type, ?int $id) {
    $this->plugins->install('fixture.greeting');

    $this->plugins->enable('fixture.greeting', $type, $id);
})->with([
    'scope lạ' => ['galaxy', 1],
    'owner có id' => ['owner', 5],
    'brand thiếu id' => ['brand', null],
])->throws(PluginOperationFailed::class);

it('CLI cài, bật, liệt kê, tắt, gỡ plugin', function () {
    $this->artisan('vani:plugin:install', ['plugin' => 'fixture.greeting'])->assertSuccessful();
    $this->artisan('vani:plugin:enable', ['plugin' => 'fixture.greeting', '--scope' => 'brand:7'])->assertSuccessful();
    $this->artisan('vani:plugin:list')->expectsOutputToContain('brand:7')->assertSuccessful();
    $this->artisan('vani:plugin:enable', ['plugin' => 'fixture.greeting', '--scope' => 'brand:x'])->assertFailed();
    $this->artisan('vani:plugin:disable', ['plugin' => 'fixture.greeting'])->assertSuccessful();
    $this->artisan('vani:plugin:uninstall', ['plugin' => 'fixture.greeting'])->assertSuccessful();
    $this->artisan('vani:plugin:install', ['plugin' => 'fixture.unknown'])->assertFailed();
});
