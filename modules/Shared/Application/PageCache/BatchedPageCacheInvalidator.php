<?php

declare(strict_types=1);

namespace Modules\Shared\Application\PageCache;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Contracts\PageCacheInvalidator;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Shared\Events\PageCacheTagsInvalidated;
use Modules\Shared\Support\DurableCache;

/**
 * Hàng đợi tag cần xoá ở store `durable` (không mất khi `cache:clear` hay LRU), lô xoá mỗi phút (ADR-051 §4). Tag gom trong
 * bộ nhớ và ghi hàng đợi **một lần** khi request/lệnh/job kết thúc (`flushBuffer`, SharedServiceProvider) — nhập file nghìn
 * sản phẩm chỉ ghi hàng đợi một lần.
 *
 * Không có cron: request trang công khai thấy lô cuối đã quá {@see self::STALE_AFTER_SECONDS} giây thì tự chạy lô sau khi
 * trả response (`processWhenStale`), một request mỗi phút.
 */
final class BatchedPageCacheInvalidator implements PageCacheInvalidator
{
    /** Thời điểm chạy lô gần nhất (store mặc định) — middleware đọc cùng lượt với phiên bản tag. */
    public const PROCESSED_AT_KEY = 'vani:page-cache:processed-at';

    public const STALE_AFTER_SECONDS = 120;

    private const PENDING_KEY = 'vani:page-cache:pending';

    private const PENDING_LOCK = 'vani:page-cache:pending-lock';

    private const FALLBACK_LOCK = 'vani:page-cache:fallback';

    /** Hàng đợi chưa chạy quá hạn này (cron và request đều không chạy) thì bỏ: bản cache đã hết hạn tối đa. */
    private const PENDING_TTL_SECONDS = 86400;

    /** Bộ nhớ đệm quá số tag này thì ghi hàng đợi ngay. */
    private const BUFFER_LIMIT = 1000;

    /** @var array<string, true> */
    private array $buffer = [];

    public function __construct(private readonly PageCacheTagVersions $versions, private readonly Cache $cache) {}

    public function invalidate(string ...$tags): void
    {
        $tags = $this->clean($tags);
        if ($tags !== []) {
            DB::afterCommit(function () use ($tags): void {
                $this->buffer += array_fill_keys($tags, true);
                if (count($this->buffer) >= self::BUFFER_LIMIT) {
                    $this->flushBuffer();
                }
            });
        }
    }

    public function invalidateNow(string ...$tags): void
    {
        $tags = $this->clean($tags);
        if ($tags !== []) {
            DB::afterCommit(fn () => $this->apply($tags));
        }
    }

    public function flush(): void
    {
        $this->invalidateNow(PageCacheTags::EVERYTHING);
    }

    /**
     * Chạy lô: lấy hết hàng đợi, đổi phiên bản, phát event. Trả số tag đã xoá.
     */
    public function process(): int
    {
        $this->flushBuffer();
        $store = DurableCache::store();
        $pending = $store->lock(self::PENDING_LOCK, 10)->block(5, function () use ($store): array {
            $pending = (array) $store->get(self::PENDING_KEY, []);
            if ($pending !== []) {
                $store->forget(self::PENDING_KEY);
            }

            return array_map('strval', array_keys($pending));
        });
        $this->apply($pending);
        $this->cache->put(self::PROCESSED_AT_KEY, time(), self::STALE_AFTER_SECONDS * 10);

        return count($pending);
    }

    /**
     * Gọi từ request khi lô cuối đã quá hạn (không có cron): một request mỗi phút chạy lô.
     */
    public function processWhenStale(): void
    {
        $this->cache->lock(self::FALLBACK_LOCK, 60)->get(fn () => $this->process());
    }

    /**
     * Tag đang chờ xoá (gỡ lỗi, Admin).
     *
     * @return list<string>
     */
    public function pending(): array
    {
        return array_map('strval', array_keys((array) DurableCache::store()->get(self::PENDING_KEY, []) + $this->buffer));
    }

    /**
     * Ghi tag đã gom vào hàng đợi (cuối request, lệnh, job).
     */
    public function flushBuffer(): void
    {
        if ($this->buffer === []) {
            return;
        }
        $tags = array_keys($this->buffer);
        $this->buffer = [];
        $store = DurableCache::store();
        $store->lock(self::PENDING_LOCK, 10)->block(5, function () use ($store, $tags): void {
            $pending = (array) $store->get(self::PENDING_KEY, []);
            $store->put(self::PENDING_KEY, $pending + array_fill_keys($tags, true), self::PENDING_TTL_SECONDS);
        });
    }

    /**
     * @param  list<string>  $tags
     */
    private function apply(array $tags): void
    {
        if ($tags === []) {
            return;
        }
        $this->versions->rotate($tags);
        event(new PageCacheTagsInvalidated($tags));
    }

    /**
     * @param  array<int, string>  $tags
     * @return list<string>
     */
    private function clean(array $tags): array
    {
        return array_values(array_unique(array_filter($tags, fn (string $tag): bool => $tag !== '')));
    }
}
