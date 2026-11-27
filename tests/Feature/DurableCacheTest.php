<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Support\DurableCache;

/*
| Store `durable` (bảng cache_durable): token và bản xem trước nhập file không mất khi xoá cache ứng dụng; dòng hết hạn
| được dọn theo lịch.
*/

beforeEach(function () {
    config(['cache.durable_store' => 'durable']);
});

it('cache:clear xoá cache ứng dụng nhưng giữ dữ liệu của store durable', function () {
    DurableCache::store()->put('vani:test-token', ['customer_id' => 7], now()->addHour());
    Cache::put('vani:test-counts', 1);

    $this->artisan('cache:clear')->assertSuccessful();

    expect(Cache::get('vani:test-counts'))->toBeNull()
        ->and(DurableCache::store()->pull('vani:test-token'))->toBe(['customer_id' => 7])
        ->and(DurableCache::store()->get('vani:test-token'))->toBeNull();
});

it('vani:cache:prune-durable xoá dòng hết hạn, giữ dòng còn hạn', function () {
    DurableCache::store()->put('vani:expired', 'cũ', now()->addMinute());
    DurableCache::store()->put('vani:fresh', 'mới', now()->addHour());
    $this->travel(2)->minutes();

    $this->artisan('vani:cache:prune-durable')->assertSuccessful();

    expect(DB::table('cache_durable')->count())->toBe(1)
        ->and(DurableCache::store()->get('vani:fresh'))->toBe('mới');
});

it('vani:cache:prune-durable dọn cả store mặc định khi dùng bảng database (bản cache trang)', function () {
    config(['cache.default' => 'database']);
    Cache::put('vani:page:cu', 'html', now()->addMinute());
    Cache::put('vani:page:moi', 'html', now()->addHour());
    $this->travel(2)->minutes();

    $this->artisan('vani:cache:prune-durable')->assertSuccessful();

    expect(DB::table('cache')->pluck('key')->all())->toBe([config('cache.prefix').'vani:page:moi']);
});

it('vani:cache:prune-durable bỏ qua khi không store nào dùng database', function () {
    config(['cache.durable_store' => 'array']);

    $this->artisan('vani:cache:prune-durable')->expectsOutputToContain('bỏ qua')->assertSuccessful();
});
