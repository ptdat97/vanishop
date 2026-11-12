<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\SellableVariant;

/**
 * Service contract: đọc catalog đang hiển thị cho storefront.
 * Dữ liệu trả về là mảng thuần (DTO dạng mảng), không có model.
 *
 * @phpstan-type VariantView array{id: int, sku: string, is_active: bool, option_values: list<array{option: string, value_id: int}>}
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
     * Danh mục đang hiện theo slug; có `breadcrumb` (0.3.66) = list<{id, slug, name}> từ gốc tới chính nó.
     *
     * @return array<string, mixed>|null
     */
    public function category(string $slug, string $locale): ?array;

    /**
     * Gợi ý tìm kiếm (0.3.71, reference-parity §3.5-4): sản phẩm khớp (tối đa $products, theo SearchProvider), danh mục
     * và thương hiệu đang hiện có tên khớp (tối đa 3 mỗi loại, không phân biệt hoa thường/dấu).
     *
     * @return array{products: list<array{id: int, slug: string, name: string|null, image_url: string|null}>, categories: list<array{id: int, slug: string, name: string}>, brands: list<array{slug: string, name: string, logo_url: ?string}>}
     */
    public function suggestions(string $text, string $locale, int $now, int $products = 5): array;

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
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, facets: array{attributes: list<array<string, mixed>>, brands: list<array{slug: string, name: string, count: int}>, options: list<array{code: string, name: string, display_type: string, values: list<array{value: string, label: string, count: int, swatch?: string}>}>, price: array{min: int, max: int}|null}, filters: list<array<string, mixed>>}
     */
    public function searchProducts(ProductFilters $filters, string $locale): array;

    /**
     * Sản phẩm đang hiển thị theo id style (0.3.43), cùng dạng item của `searchProducts` (có "variant_ids"), giữ thứ tự
     * `$styleIds`; id không hiển thị/không tồn tại bị bỏ.
     *
     * @param  list<int>  $styleIds
     * @return list<array<string, mixed>>
     */
    public function productsByIds(array $styleIds, string $locale, int $now): array;

    /**
     * Chi tiết sản phẩm đang hiển thị; có "variants" (list<VariantView>) các variant đang bán, "videos" (video của driver
     * đang bật) và "links" (0.3.46: list<{code, name, style_ids}> — id có thể gồm sản phẩm không hiển thị, lọc bằng
     * `productsByIds`).
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
