<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\PageCache;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Shared\Application\PageCache\PageCacheTagVersions;
use Modules\Shared\Contracts\PageCacheTags;

/**
 * Bản HTML đã cache ở store mặc định (mất thì chỉ render lại, ADR-051 §1). Mỗi bản lưu kèm phiên bản của mọi tag lúc
 * render; đọc thấy lệch một tag là coi như không có (§3).
 *
 * @phpstan-type Entry array{content: string, status: int, headers: array<string, string>, tags: list<string>, versions: array<string, string>, stored_at: int, expires_at: int}
 */
final class PageCacheStore
{
    /** Đổi mỗi lần lô xoá chạy: render bắt đầu trước lô mà lưu sau lô thì bỏ (có thể đã đọc dữ liệu cũ). */
    private const GENERATION_KEY = 'vani:page-cache:generation';

    public function __construct(private readonly Cache $cache, private readonly PageCacheTagVersions $versions) {}

    /**
     * Bản còn hiệu lực, hoặc null. `$schedulerStale` = lô xoá đã lâu không chạy (không có cron).
     *
     * @return Entry|null
     */
    public function get(string $key, ?bool &$schedulerStale = null): ?array
    {
        $entry = $this->cache->get($key);
        if (! is_array($entry) || ! isset($entry['content'], $entry['tags'], $entry['versions'], $entry['expires_at'])) {
            return null;
        }
        [$current, $extra] = $this->versions->current($entry['tags'], [BatchedPageCacheInvalidator::PROCESSED_AT_KEY]);
        $processedAt = $extra[BatchedPageCacheInvalidator::PROCESSED_AT_KEY] ?? null;
        $schedulerStale = ! is_numeric($processedAt) || time() - (int) $processedAt > BatchedPageCacheInvalidator::STALE_AFTER_SECONDS;

        return $current === $entry['versions'] ? $entry : null;
    }

    public function generation(): mixed
    {
        return $this->cache->get(self::GENERATION_KEY);
    }

    /**
     * @param  list<string>  $tags
     * @param  array<string, string>  $headers
     */
    public function put(string $key, string $content, int $status, array $headers, array $tags, int $ttl, mixed $generation): bool
    {
        $tags = array_values(array_unique([PageCacheTags::EVERYTHING, ...$tags]));
        [$versions] = $this->versions->current($tags);
        if ($this->generation() !== $generation) {
            return false;
        }

        return $this->cache->put($key, [
            'content' => $content, 'status' => $status, 'headers' => $headers, 'tags' => $tags, 'versions' => $versions, 'stored_at' => time(),
            'expires_at' => time() + $ttl,
        ], $ttl);
    }

    /**
     * Gọi khi lô xoá chạy (event PageCacheTagsInvalidated).
     */
    public function advanceGeneration(): void
    {
        $this->cache->forever(self::GENERATION_KEY, bin2hex(random_bytes(6)));
    }

    /**
     * Chống dồn tải (§6): một request render, request khác cùng trang chờ ngắn rồi đọc lại; chờ quá hạn thì tự render.
     *
     * @param  Closure(bool $owner): mixed  $render  `$owner` = request này giữ khoá (được lưu bản cache)
     * @param  Closure(): (array<string, mixed>|null)  $reread
     */
    public function rendering(string $key, Closure $render, Closure $reread): mixed
    {
        $lock = $this->cache->lock($key.':lock', (int) config('vanishop.storefront.page_cache.lock_seconds', 10));
        if ($lock->get()) {
            try {
                return $render(true);
            } finally {
                $lock->release();
            }
        }

        $deadline = microtime(true) + ((int) config('vanishop.storefront.page_cache.lock_wait_ms', 3000)) / 1000;
        while (microtime(true) < $deadline) {
            usleep(100_000);
            $entry = $reread();
            if ($entry !== null) {
                return $entry;
            }
        }

        return $render(false);
    }
}
