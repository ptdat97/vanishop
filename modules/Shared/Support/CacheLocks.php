<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use LogicException;

/**
 * Khoá nguyên tử trên một store cache. Interface `Cache\Repository` không khai báo `lock()` (chỉ store nào implement
 * `LockProvider` mới có): lấy khoá qua đây thay vì gọi `$cache->lock()` theo magic method.
 */
final class CacheLocks
{
    public static function lock(Repository $cache, string $name, int $seconds = 0): Lock
    {
        $store = $cache instanceof CacheRepository ? $cache->getStore() : null;
        if (! $store instanceof LockProvider) {
            throw new LogicException('Store cache không hỗ trợ khoá (cần redis, database, file, array…).');
        }

        return $store->lock($name, $seconds);
    }
}
