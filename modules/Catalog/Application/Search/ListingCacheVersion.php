<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Phiên bản read model trang danh sách (db-refactor L56): tăng mỗi khi `product_listing` / `product_facet_values` đổi
 * (ProductListingProjector, giá niêm yết, còn hàng, xoá giá trị thuộc tính). ProductSearch dùng làm khoá cache số đếm và facet
 * — đổi dữ liệu là cache cũ hết hiệu lực ngay, không cần xoá từng khoá.
 */
final class ListingCacheVersion
{
    private const KEY = 'catalog:listing:version';

    public function __construct(private readonly Cache $cache) {}

    public function current(): int
    {
        return (int) $this->cache->get(self::KEY, 0);
    }

    public function bump(): void
    {
        // Store `database` không tự tạo khoá khi increment — tạo trước bằng add (nguyên tử).
        $this->cache->add(self::KEY, 0);
        $this->cache->increment(self::KEY);
    }
}
