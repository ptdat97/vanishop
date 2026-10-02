<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\SellableVariant;

/**
 * Service contract: đọc catalog đang hiển thị cho storefront.
 * Dữ liệu trả về là mảng thuần (DTO dạng mảng), không có model.
 *
 * @phpstan-type VariantView array{id: int, sku: string, color_code: string, size_code: string, size_system: string}
 */
interface CatalogReader
{
    /**
     * Cây danh mục đang hiển thị.
     *
     * @return list<array<string, mixed>>
     */
    public function categoryTree(string $locale): array;

    /**
     * @return array<string, mixed>|null
     */
    public function category(string $slug, string $locale): ?array;

    /**
     * Thương hiệu đang hiện (status = active), theo thứ tự hiển thị.
     *
     * @return list<array{id: int, code: string, slug: string, name: string, description: ?string, logo_url: ?string}>
     */
    public function brands(): array;

    /**
     * @return array{id: int, code: string, slug: string, name: string, description: ?string, logo_url: ?string, meta_title: ?string, meta_description: ?string}|null
     */
    public function brand(string $slug): ?array;

    /**
     * Danh sách sản phẩm đang hiển thị; mỗi item có "variant_ids" (các variant đang bán).
     *
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, facets: array{color_families: array<string, int>, attributes: list<array<string, mixed>>, brands: list<array{slug: string, name: string, count: int}>}}
     */
    public function searchProducts(ProductFilters $filters, string $locale): array;

    /**
     * Chi tiết sản phẩm đang hiển thị; có "variants" (list<VariantView>) các variant đang bán.
     *
     * @return array<string, mixed>|null
     */
    public function productDetail(string $slug, string $locale, int $now): ?array;

    /**
     * Variant đang bán được (variant active + sản phẩm đang hiển thị) — cho giỏ hàng, checkout.
     * Variant không bán được thì không có mặt.
     *
     * @param  list<int>  $variantIds
     * @return array<int, SellableVariant>
     */
    public function sellableVariants(array $variantIds, string $locale, int $now): array;
}
