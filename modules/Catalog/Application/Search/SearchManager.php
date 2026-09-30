<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use InvalidArgumentException;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Extension\Contracts\Extensions;

/**
 * Chọn SearchProvider theo cấu hình (VANI_SEARCH_PROVIDER) trong các provider có hiệu lực trong phạm vi hiện tại
 * (SearchProvider do plugin cung cấp chỉ có mặt khi plugin được bật).
 */
final class SearchManager
{
    /** @deprecated dùng {@see SearchProvider::TAG} (public API). */
    public const TAG = SearchProvider::TAG;

    private const FALLBACK = 'database';

    public function __construct(
        private readonly Extensions $extensions,
        private readonly string $providerCode,
    ) {}

    /**
     * Tìm kiếm cho storefront. Provider cấu hình (vd. Meilisearch) lỗi → dùng provider `database` để trang danh
     * sách vẫn hoạt động.
     */
    public function search(ProductSearchQuery $query): ProductSearchResult
    {
        $provider = $this->provider();
        $fallback = $this->extensions->implementations(SearchProvider::TAG, SearchProvider::class)[self::FALLBACK] ?? null;
        if ($fallback === null || $fallback === $provider) {
            return $provider->search($query);
        }

        return $this->extensions->call($provider, fn (): ProductSearchResult => $provider->search($query), null, 'catalog.search')
            ?? $fallback->search($query);
    }

    public function provider(): SearchProvider
    {
        return $this->extensions->implementations(SearchProvider::TAG, SearchProvider::class)[$this->providerCode]
            ?? throw new InvalidArgumentException("Search provider [{$this->providerCode}] chưa được đăng ký.");
    }
}
