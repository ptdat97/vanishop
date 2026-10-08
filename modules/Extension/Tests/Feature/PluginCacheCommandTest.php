<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Modules\Extension\Application\Plugins\PluginStateCache;
use Modules\Extension\Persistence\Models\PluginRecord;

it('bản release mới chưa có file cache: vani:plugin:cache dựng lại từ DB các plugin đang bật; chạy trong optimize, không trong optimize:clear', function () {
    // File cache riêng: test song song (--parallel) dùng chung file mặc định của môi trường testing.
    $path = storage_path('framework/testing/plugins-cache-'.bin2hex(random_bytes(4)).'.php');
    app()->instance(PluginStateCache::class, new PluginStateCache(new Filesystem, $path));
    $cache = app(PluginStateCache::class);
    $enabled = PluginRecord::query()->get()->filter(fn (PluginRecord $record): bool => $record->status->loadsProvider())->pluck('id')->sort()->values()->all();
    expect($enabled)->toContain('vani.cod');

    $cache->forget();
    expect($cache->read())->toBe([]);

    $this->artisan('vani:plugin:cache')->assertSuccessful();
    expect(collect($cache->read())->pluck('id')->sort()->values()->all())->toBe($enabled)
        ->and(ServiceProvider::$optimizeCommands)->toContain('vani:plugin:cache')
        ->and(ServiceProvider::$optimizeClearCommands)->not->toContain('vani:plugin:cache');

    @unlink($path);
});
