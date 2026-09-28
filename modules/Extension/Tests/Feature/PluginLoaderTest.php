<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\PluginLoader;
use Modules\Extension\Application\Plugins\PluginStateCache;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookType;
use Modules\Extension\Tests\Fixtures\GreetingPluginProvider;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

beforeEach(function () {
    app(HookRegistry::class)->declare(new HookDefinition('test.greeting', HookType::Filter, true, '0.1'));
});

afterEach(function () {
    File::delete(File::glob(storage_path('framework/testing/loader-*.php')));
});

function cacheWith(array $plugins): PluginStateCache
{
    $cache = new PluginStateCache(new Filesystem, storage_path('framework/testing/loader-'.uniqid().'.php'));
    $cache->write($plugins);

    return $cache;
}

it('nạp provider của plugin đã cài và cô lập plugin lỗi', function () {
    $cache = cacheWith([
        ['id' => 'fixture.broken', 'provider' => 'Plugin\\DoesNotExist\\Provider', 'path' => '/x', 'status' => 'enabled'],
        ['id' => 'fixture.greeting', 'provider' => GreetingPluginProvider::class, 'path' => '/y', 'status' => 'enabled'],
    ]);

    $loader = new PluginLoader($this->app, $cache, safeMode: false);
    $loader->load();

    expect($loader->loaded())->toBe(['fixture.greeting'])
        ->and(array_keys($loader->failures()))->toBe(['fixture.broken'])
        ->and($this->app->getProvider(GreetingPluginProvider::class))->not->toBeNull();
});

it('safe mode không nạp plugin nào', function () {
    $cache = cacheWith([['id' => 'fixture.greeting', 'provider' => GreetingPluginProvider::class, 'path' => '/y', 'status' => 'enabled']]);

    $loader = new PluginLoader($this->app, $cache, safeMode: true);
    $loader->load();

    expect($loader->loaded())->toBe([]);
});

it('plugin nghe hook chưa khai báo bị cô lập thay vì làm hỏng ứng dụng', function () {
    $cache = cacheWith([['id' => 'fixture.greeting', 'provider' => GreetingPluginProvider::class, 'path' => '/y', 'status' => 'enabled']]);
    // Registry không khai báo test.greeting (và HookManager mới dùng registry đó).
    $this->app->instance(HookRegistry::class, new HookRegistry);
    $this->app->forgetInstance(HookManager::class);

    $loader = new PluginLoader($this->app, $cache, safeMode: false);
    $loader->load();

    expect($loader->loaded())->toBe([])
        ->and($loader->failures()['fixture.greeting'])->toContain('test.greeting');
});
