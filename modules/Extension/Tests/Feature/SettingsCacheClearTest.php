<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Shared\Events\PageCacheTagsInvalidated;
use Modules\Shared\Support\DurableCache;

/*
| Admin → Cài đặt → "Xoá cache": quyền settings.manage; xoá store mặc định (cache trang, cài đặt…), giữ store durable,
| báo CDN xoá toàn bộ cache trang; Redis cache dùng chung database với phiên/queue thì từ chối.
*/

beforeEach(function () {
    config(['cache.durable_store' => 'durable']);
    $this->as = fn (array $permissions) => $this->actingAs(T::staff(['admin.access', ...$permissions]), 'staff');
});

it('chỉ settings.manage thấy và dùng được nút', function () {
    ($this->as)(['settings.seo']);
    $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page->where('clearCacheUrl', null));
    $this->post('/admin/settings/cache/clear')->assertForbidden();

    ($this->as)(['settings.manage']);
    $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page->where('clearCacheUrl', route('admin.settings.cache.clear')));
});

it('xoá store mặc định, giữ store durable, phát event xoá toàn bộ cache trang, có audit', function () {
    Cache::put('vani:page:thu', 'html', 3600);
    DurableCache::store()->put('vani:token:thu', 'giu', 3600);
    Event::fake([PageCacheTagsInvalidated::class]);

    ($this->as)(['settings.manage']);
    $this->post('/admin/settings/cache/clear')->assertRedirect()->assertSessionHas('success');

    expect(Cache::get('vani:page:thu'))->toBeNull()
        ->and(DurableCache::store()->get('vani:token:thu'))->toBe('giu')
        ->and(AuditLog::query()->where('action', 'settings.cache_cleared')->exists())->toBeTrue();
    Event::assertDispatched(PageCacheTagsInvalidated::class, fn (PageCacheTagsInvalidated $event): bool => $event->everything());
});

it('store durable trùng store mặc định: từ chối, không xoá gì', function () {
    config(['cache.durable_store' => config('cache.default')]);
    Cache::put('vani:token:thu', 'giu', 3600);

    ($this->as)(['settings.manage']);
    $this->post('/admin/settings/cache/clear')->assertSessionHasErrors('business');
    expect(Cache::get('vani:token:thu'))->toBe('giu');
});

it('Redis cache dùng chung database với phiên/queue: từ chối, không xoá gì', function () {
    config([
        'cache.default' => 'redis',
        'cache.stores.redis' => ['driver' => 'redis', 'connection' => 'cache'],
        'database.redis.cache' => config('database.redis.default'),
    ]);

    ($this->as)(['settings.manage']);
    $this->post('/admin/settings/cache/clear')->assertSessionHasErrors('business');
});
