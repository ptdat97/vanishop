<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Application\PageCache\PageCacheTagVersions;

/*
| Phiên bản tag cache trang (ADR-051 §3) trên store `database`: trang có nhiều tag chưa có phiên bản (sau "Xoá cache", tag
| mới) chỉ tốn một lượt đọc và một lượt ghi, không một insert cho mỗi tag.
*/

it('nhiều tag chưa có phiên bản: một lượt đọc + một lượt ghi; lần sau đọc lại đúng phiên bản đó', function () {
    config(['cache.default' => 'database']);
    $versions = new PageCacheTagVersions(Cache::store());
    $tags = array_map(fn (int $id): string => "product:{$id}", range(1, 40));

    DB::enableQueryLog();
    [$created] = $versions->current($tags);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBe(2)
        ->and($created)->toHaveCount(40)
        ->and($versions->current($tags)[0])->toBe($created);
});

it('đổi phiên bản một tag: chỉ tag đó khác', function () {
    config(['cache.default' => 'database']);
    $versions = new PageCacheTagVersions(Cache::store());
    [$before] = $versions->current(['product:1', 'product:2']);

    $versions->rotate(['product:1']);
    [$after] = $versions->current(['product:1', 'product:2']);

    expect($after['product:1'])->not->toBe($before['product:1'])
        ->and($after['product:2'])->toBe($before['product:2']);
});
