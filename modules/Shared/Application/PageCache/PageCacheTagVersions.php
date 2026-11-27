<?php

declare(strict_types=1);

namespace Modules\Shared\Application\PageCache;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

/**
 * Phiên bản của từng tag cache trang (ADR-051 §3): chuỗi ngẫu nhiên ở store mặc định. Xoá theo tag = đổi phiên bản; bản
 * cache lưu kèm phiên bản lúc render nên lệch một tag là hết hiệu lực — không phải tìm và xoá từng bản ghi, chạy được trên
 * mọi store (kể cả `database`). Khoá phiên bản bị đẩy ra khỏi store thì sinh phiên bản mới, tức là coi như đã xoá.
 */
final class PageCacheTagVersions
{
    private const PREFIX = 'vani:page-cache:tag:';

    /** Khoá phiên bản sống lâu hơn nhiều so với hạn của bản cache trang. */
    private const TTL_SECONDS = 7 * 86400;

    public function __construct(private readonly Cache $cache) {}

    /**
     * Phiên bản hiện tại (một lệnh đọc nhiều khoá); tag chưa có phiên bản thì tạo.
     *
     * @param  list<string>  $tags
     * @param  list<string>  $extraKeys  khoá khác đọc cùng lượt (tránh thêm một lượt đọc store)
     * @return array{0: array<string, string>, 1: array<string, mixed>} [tag => phiên bản, khoá thêm => giá trị]
     */
    public function current(array $tags, array $extraKeys = []): array
    {
        $keys = array_map(fn (string $tag): string => self::PREFIX.$tag, $tags);
        $values = $this->cache->many([...$keys, ...$extraKeys]);

        $versions = [];
        $missing = [];
        foreach ($tags as $tag) {
            $version = $values[self::PREFIX.$tag] ?? null;
            if (! is_string($version)) {
                $version = Str::random(12);
                $missing[self::PREFIX.$tag] = $version;
            }
            $versions[$tag] = $version;
        }
        // Tag chưa có phiên bản (sau `cache:clear`, tag mới): ghi một lần cho cả trang. Hai request cùng ghi thì bản sau
        // thắng — bản cache lưu với phiên bản của request trước chỉ thành MISS, không bao giờ phục vụ dữ liệu cũ.
        if ($missing !== []) {
            $this->cache->putMany($missing, self::TTL_SECONDS);
        }

        return [$versions, array_intersect_key($values, array_flip($extraKeys))];
    }

    /**
     * @param  list<string>  $tags
     */
    public function rotate(array $tags): void
    {
        if ($tags === []) {
            return;
        }
        $this->cache->putMany(array_combine(
            array_map(fn (string $tag): string => self::PREFIX.$tag, $tags),
            array_map(fn (): string => Str::random(12), $tags),
        ), self::TTL_SECONDS);
    }
}
