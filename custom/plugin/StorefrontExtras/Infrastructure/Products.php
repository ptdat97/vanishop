<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure;

use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Storefront\Contracts\ProductCards;

/**
 * Thẻ sản phẩm cho khối: theo nguồn (mới nhất / danh mục / bộ sưu tập / thương hiệu / chọn tay) — cùng dạng thẻ của Core.
 */
final class Products
{
    public const SOURCES = ['newest' => 'Mới nhất', 'category' => 'Danh mục', 'collection' => 'Bộ sưu tập', 'brand' => 'Thương hiệu', 'ids' => 'Chọn tay (id sản phẩm)'];

    public function __construct(
        private readonly CatalogReader $catalog,
        private readonly ProductCards $cards,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function from(string $source, string $value, int $limit, string $locale): array
    {
        $limit = max(1, min(24, $limit));
        if ($source === 'ids') {
            $ids = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $value) ?: []), fn (int $id): bool => $id > 0));

            return $this->cards->forIds(array_slice($ids, 0, $limit), $locale);
        }

        $value = trim($value);
        $items = $this->catalog->searchProducts(new ProductFilters(
            now: now()->getTimestamp(),
            categorySlug: $source === 'category' && $value !== '' ? $value : null,
            collectionSlug: $source === 'collection' && $value !== '' ? $value : null,
            perPage: $limit,
            brandSlugs: $source === 'brand' && $value !== '' ? [$value] : [],
        ), $locale)['items'];

        return $this->cards->forIds(array_map(fn (array $item): int => (int) $item['id'], $items), $locale);
    }

    /**
     * @param  list<int>  $styleIds
     * @return list<array<string, mixed>>
     */
    public function byIds(array $styleIds, string $locale): array
    {
        return $styleIds === [] ? [] : $this->cards->forIds($styleIds, $locale);
    }

    /** Link nội bộ (`/…`) hoặc http(s) — chặn `javascript:`… */
    public static function safeUrl(mixed $url): string
    {
        $url = trim((string) $url);

        return preg_match('#^(/(?!/)|https?://)#i', $url) === 1 ? $url : '';
    }

    /**
     * Ảnh của ô/slide: ưu tiên ảnh Thư viện ảnh (id, có srcset — Core 0.3.87), không có thì URL nhập tay.
     *
     * @return array{string, string} [src, srcset]
     */
    public static function image(mixed $mediaId, mixed $url, int $width): array
    {
        if (is_numeric($mediaId) && (int) $mediaId > 0 && ($src = vani_image((int) $mediaId, $width)) !== '') {
            return [$src, vani_srcset((int) $mediaId)];
        }

        return [self::safeUrl($url), ''];
    }
}
