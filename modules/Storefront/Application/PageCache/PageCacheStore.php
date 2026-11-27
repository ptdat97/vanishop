<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\PageCache;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Shared\Application\PageCache\PageCacheTagVersions;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Shared\Support\CacheLocks;
use Throwable;

/**
 * Bản HTML đã cache ở store mặc định (mất thì chỉ render lại, ADR-051 §1). Mỗi bản lưu kèm phiên bản của mọi tag lúc
 * render; đọc thấy lệch một tag là coi như không có (§3).
 *
 * **Lỗi store không làm hỏng trang** (Redis mất kết nối, bảng `cache` lỗi): đọc lỗi = không có bản cache, ghi lỗi = không
 * lưu, khoá lỗi = render luôn; `failed()` cho biết để trả `X-Vani-Cache-Reason: cache_error`. Mỗi request log một lần.
 *
 * @phpstan-type Entry array{content: string, status: int, headers: array<string, string>, tags: list<string>, versions: array<string, string>, stored_at: int, expires_at: int}
 */
final class PageCacheStore
{
    /** Đổi mỗi lần lô xoá chạy: render bắt đầu trước lô mà lưu sau lô thì bỏ (có thể đã đọc dữ liệu cũ). */
    private const GENERATION_KEY = 'vani:page-cache:generation';

    private bool $failed = false;

    public function __construct(private readonly Cache $cache, private readonly PageCacheTagVersions $versions) {}

    public function failed(): bool
    {
        return $this->failed;
    }

    /**
     * Bản còn hiệu lực, hoặc null. `$schedulerStale` = lô xoá đã lâu không chạy (không có cron).
     *
     * @return Entry|null
     */
    public function get(string $key, ?bool &$schedulerStale = null): ?array
    {
        $schedulerStale = false;

        return $this->guard(function () use ($key, &$schedulerStale): ?array {
            return $this->read($key, $schedulerStale);
        }, null);
    }

    /**
     * @return Entry|null
     */
    private function read(string $key, ?bool &$schedulerStale): ?array
    {
        $entry = $this->cache->get($key);
        if (! is_array($entry) || ! isset($entry['content'], $entry['tags'], $entry['versions'], $entry['expires_at'])) {
            return null;
        }
        // Hết hạn theo mốc đã tính lúc lưu (lịch bảng giá, khung giờ sản phẩm), không chỉ dựa vào TTL của store.
        if ((int) $entry['expires_at'] <= now()->getTimestamp()) {
            return null;
        }
        [$current, $extra] = $this->versions->current($entry['tags'], [BatchedPageCacheInvalidator::PROCESSED_AT_KEY]);
        $processedAt = $extra[BatchedPageCacheInvalidator::PROCESSED_AT_KEY] ?? null;
        $schedulerStale = ! is_numeric($processedAt) || now()->getTimestamp() - (int) $processedAt > BatchedPageCacheInvalidator::STALE_AFTER_SECONDS;

        return $current === $entry['versions'] ? $entry : null;
    }

    public function generation(): mixed
    {
        return $this->guard(fn (): mixed => $this->cache->get(self::GENERATION_KEY), null);
    }

    /**
     * @param  list<string>  $tags
     * @param  array<string, string>  $headers
     */
    public function put(string $key, string $content, int $status, array $headers, array $tags, int $ttl, mixed $generation): bool
    {
        $tags = array_values(array_unique([PageCacheTags::EVERYTHING, ...$tags]));

        return $this->guard(function () use ($key, $content, $status, $headers, $tags, $ttl, $generation): bool {
            [$versions] = $this->versions->current($tags);
            if ($this->cache->get(self::GENERATION_KEY) !== $generation) {
                return false;
            }

            return $this->cache->put($key, [
                'content' => $content, 'status' => $status, 'headers' => $headers, 'tags' => $tags, 'versions' => $versions, 'stored_at' => now()->getTimestamp(),
                'expires_at' => now()->getTimestamp() + $ttl,
            ], $ttl);
        }, false);
    }

    /**
     * Gọi khi lô xoá chạy (event PageCacheTagsInvalidated).
     */
    public function advanceGeneration(): void
    {
        $this->guard(fn () => $this->cache->put(self::GENERATION_KEY, bin2hex(random_bytes(6)), 86400), null);
    }

    /**
     * Chống dồn tải (§6): một request render, request khác cùng trang chờ ngắn rồi đọc lại; chờ quá hạn thì tự render.
     *
     * @param  Closure(bool $owner): mixed  $render  `$owner` = request này giữ khoá (được lưu bản cache)
     * @param  Closure(): (array<string, mixed>|null)  $reread
     */
    public function rendering(string $key, Closure $render, Closure $reread): mixed
    {
        $lock = $this->guard(function () use ($key) {
            $lock = CacheLocks::lock($this->cache, $key.':lock', (int) config('vanishop.storefront.page_cache.lock_seconds', 10));

            return $lock->get() ? $lock : false;
        }, null);
        if ($lock === null) {
            return $render(true); // store lỗi: render luôn (lưu cũng sẽ lỗi, không sao)
        }
        if ($lock !== false) {
            try {
                return $render(true);
            } finally {
                $this->guard(fn () => $lock->release(), null);
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

    /**
     * @template T
     *
     * @param  Closure(): T  $operation
     * @param  T  $fallback
     * @return T
     */
    private function guard(Closure $operation, mixed $fallback): mixed
    {
        try {
            return $operation();
        } catch (Throwable $exception) {
            if (! $this->failed) {
                Log::warning('Cache trang: store lỗi, phục vụ không cache.', ['error' => $exception->getMessage()]);
            }
            $this->failed = true;

            return $fallback;
        }
    }
}
