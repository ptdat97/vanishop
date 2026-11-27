<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Extension\Contracts\SettingPermissions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Contracts\PageCacheInvalidator;

/**
 * Admin → Cài đặt → "Xoá cache": xoá store cache mặc định như `php artisan cache:clear` (cache trang, cài đặt, số đếm,
 * menu, sitemap — đều tính lại được) rồi phát event xoá toàn bộ cache trang cho plugin CDN (ADR-051). Không đụng store
 * `durable` (token, bản xem trước nhập file, hàng đợi xoá cache trang) và ảnh thu nhỏ (Thư viện ảnh có nút riêng).
 */
final class CacheController
{
    public function __invoke(PageCacheInvalidator $pageCache, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize(SettingPermissions::ALL);
        if (self::sharesRedisWithAppData()) {
            throw ValidationException::withMessages(['business' => __('extension::messages.cache_shares_redis_database')]);
        }
        // Store durable trùng store mặc định: xoá cache là mất token, bản xem trước nhập file.
        if ((string) config('cache.durable_store', 'durable') === (string) config('cache.default')) {
            throw ValidationException::withMessages(['business' => __('extension::messages.cache_shares_durable_store')]);
        }

        Cache::store()->flush();
        $pageCache->flush();
        $audit->record('settings.cache_cleared', 'settings', 'cache', ['store' => (string) config('cache.default')]);

        return back()->with('success', __('extension::messages.cache_cleared'));
    }

    /**
     * Store Redis dùng chung database với phiên/queue: xoá cache (FLUSHDB) sẽ xoá cả phiên đăng nhập và job.
     */
    private static function sharesRedisWithAppData(): bool
    {
        $store = (array) config('cache.stores.'.config('cache.default'), []);
        if (($store['driver'] ?? null) !== 'redis') {
            return false;
        }
        $database = fn (string $connection): string => (string) config("database.redis.{$connection}.host").':'.config("database.redis.{$connection}.port").'/'.config("database.redis.{$connection}.database");
        $cache = $database((string) ($store['connection'] ?? 'default'));

        return $cache === $database('default');
    }
}
