<?php

use Illuminate\Support\ServiceProvider;
use Modules\Extension\Application\Plugins\PluginStateCache;
use Modules\Extension\Persistence\Models\PluginRecord;

it('bản release mới chưa có file cache: vani:plugin:cache dựng lại từ DB các plugin đang bật; chạy trong optimize, không trong optimize:clear', function () {
    $cache = app(PluginStateCache::class);
    $enabled = PluginRecord::query()->get()->filter(fn (PluginRecord $record): bool => $record->status->loadsProvider())->pluck('id')->sort()->values()->all();
    expect($enabled)->toContain('vani.cod');

    $cache->forget();
    expect($cache->read())->toBe([]);

    $this->artisan('vani:plugin:cache')->assertSuccessful();
    expect(collect($cache->read())->pluck('id')->sort()->values()->all())->toBe($enabled)
        ->and(ServiceProvider::$optimizeCommands)->toContain('vani:plugin:cache')
        ->and(ServiceProvider::$optimizeClearCommands)->not->toContain('vani:plugin:cache');
});
