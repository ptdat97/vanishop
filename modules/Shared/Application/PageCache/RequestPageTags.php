<?php

declare(strict_types=1);

namespace Modules\Shared\Application\PageCache;

use Modules\Shared\Contracts\PageCacheTags;

/**
 * Bộ gom tag của một request (scoped). Middleware cache trang đọc `tags()` và `uncacheableReason()` sau khi render.
 */
final class RequestPageTags implements PageCacheTags
{
    /** @var array<string, true> */
    private array $tags = [];

    private ?string $uncacheable = null;

    public function add(string ...$tags): void
    {
        foreach ($tags as $tag) {
            if ($tag !== '') {
                $this->tags[$tag] = true;
            }
        }
    }

    public function products(iterable $productIds): void
    {
        foreach ($productIds as $id) {
            $this->tags['product:'.(int) $id] = true;
        }
    }

    /**
     * Bắt đầu một trang mới (middleware cache trang): bỏ tag của request trước khi tiến trình phục vụ nhiều request.
     */
    public function reset(): void
    {
        [$this->tags, $this->uncacheable] = [[], null];
    }

    public function uncacheable(string $reason): void
    {
        $this->uncacheable ??= $reason;
    }

    /**
     * Tag đã ghi; quá `$maxProducts` tag sản phẩm thì thay bằng `catalog:listing` (giữ chi phí kiểm tra phiên bản nhỏ).
     *
     * @return list<string>
     */
    public function tags(int $maxProducts = PHP_INT_MAX): array
    {
        $tags = array_keys($this->tags);
        $products = array_filter($tags, fn (string $tag): bool => str_starts_with($tag, 'product:'));
        if (count($products) > $maxProducts) {
            $tags = [...array_diff($tags, $products), self::CATALOG_LISTING];
        }
        $tags = array_values(array_unique($tags));
        sort($tags);

        return $tags;
    }

    public function uncacheableReason(): ?string
    {
        return $this->uncacheable;
    }
}
