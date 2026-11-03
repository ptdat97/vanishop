<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\ResolvedPrice;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Storefront\Contracts\ProductCards;

/**
 * Ghép dữ liệu sản phẩm cho storefront từ các module Core qua contract (Catalog + Pricing + Inventory).
 * Storefront chỉ công bố còn/hết hàng và "sắp hết", không lộ số tồn chính xác.
 * Native storefront và Storefront API cùng dùng lớp này (ADR-009). Giá bị ẩn theo `PriceVisibility` → `price: null`,
 * `price_hidden: true` (cả variant); plugin enricher nhận dữ liệu đã ẩn.
 */
final class ProductViews implements ProductCards
{
    public function __construct(
        private readonly CatalogReader $catalog,
        private readonly PriceResolver $prices,
        private readonly AvailabilityReader $availability,
        private readonly MoneyFormatter $money,
        private readonly Enrichment $enrichment,
        private readonly PriceVisibility $visibility,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, facets: array<string, mixed>}
     */
    public function listing(ProductFilters $filters, string $locale, int $now): array
    {
        $listing = $this->catalog->searchProducts($filters, $locale);
        $listing['items'] = $this->cards($listing['items'], $now);

        return $listing;
    }

    public function forIds(array $styleIds, string $locale): array
    {
        $now = now()->getTimestamp();
        $ids = array_slice($styleIds, 0, self::MAX_IDS);

        return $this->cards($this->catalog->productsByIds($ids, $locale, $now), $now);
    }

    /**
     * Item catalog (có "variant_ids") → thẻ sản phẩm: khoảng giá, còn hàng, phần mở rộng của plugin.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function cards(array $items, int $now): array
    {
        $variantIds = array_merge(...array_map(fn (array $item): array => $item['variant_ids'], $items ?: [['variant_ids' => []]]));
        $resolved = $this->resolve($variantIds, $now);
        $stock = $this->stock(array_keys($resolved));

        $hidden = $this->visibility->hidden();
        $items = array_map(function (array $item) use ($resolved, $stock, $hidden): array {
            $item['price'] = $hidden ? null : $this->range(array_values(array_intersect_key($resolved, array_flip($item['variant_ids']))));
            $item['price_hidden'] = $hidden;
            $item['in_stock'] = array_sum(array_intersect_key($stock, array_flip($item['variant_ids']))) > 0;
            unset($item['variant_ids']);

            return $item;
        }, $items);

        return $this->enrichment->apply('product_card', $items);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $slug, string $locale, int $now): ?array
    {
        $product = $this->catalog->productDetail($slug, $locale, $now);
        if ($product === null) {
            return null;
        }

        $resolved = $this->resolve(array_column($product['variants'], 'id'), $now);
        $stock = $this->stock(array_keys($resolved));
        $lowStock = (int) config('vanishop.inventory.low_stock_threshold', 3);
        $hidden = $this->visibility->hidden();

        $product['variants'] = array_map(fn (array $variant): array => [
            ...$variant,
            'price' => ! $hidden && isset($resolved[$variant['id']]) ? $this->price($resolved[$variant['id']]) : null,
            'available' => ($stock[$variant['id']] ?? 0) > 0,
            'low_stock' => ($stock[$variant['id']] ?? 0) > 0 && $stock[$variant['id']] <= $lowStock,
        ], $product['variants']);
        $product['price'] = $hidden ? null : $this->range(array_values($resolved));
        $product['price_hidden'] = $hidden;
        $product['in_stock'] = array_sum($stock) > 0;
        $product['links'] = $this->links($product['links'] ?? [], $locale, $now);
        unset($product['variant_ids']);

        return $this->enrichment->applyOne('product', $product);
    }

    /**
     * Khoảng giá chung theo style (cùng dạng `price` của thẻ) cho trang cache khi ẩn giá — gọi sau khi đã kiểm tra khách
     * được xem giá (`GET /_vani/gia`). Style không hiển thị hoặc chưa có giá bị bỏ.
     *
     * @param  list<int>  $styleIds
     * @return array<int, array<string, mixed>> style id => khoảng giá
     */
    public function priceRanges(array $styleIds, string $locale): array
    {
        $now = now()->getTimestamp();
        $items = $this->catalog->productsByIds(array_slice($styleIds, 0, self::MAX_IDS), $locale, $now);
        $resolved = $this->resolve(array_merge(...array_map(fn (array $item): array => $item['variant_ids'], $items ?: [['variant_ids' => []]])), $now);

        $ranges = [];
        foreach ($items as $item) {
            $range = $this->range(array_values(array_intersect_key($resolved, array_flip($item['variant_ids']))));
            if ($range !== null) {
                $ranges[(int) $item['id']] = $range;
            }
        }

        return $ranges;
    }

    /**
     * Sản phẩm liên kết → thẻ sản phẩm (một lần truy vấn cho mọi loại); loại không còn sản phẩm hiển thị bị bỏ.
     *
     * @param  list<array{code: string, name: string|null, style_ids: list<int>}>  $links
     * @return list<array{code: string, name: string|null, products: list<array<string, mixed>>}>
     */
    private function links(array $links, string $locale, int $now): array
    {
        $ids = array_values(array_unique(array_merge(...array_column($links, 'style_ids') ?: [[]])));
        if ($ids === []) {
            return [];
        }

        $cards = array_column($this->cards($this->catalog->productsByIds($ids, $locale, $now), $now), null, 'id');

        $result = [];
        foreach ($links as $link) {
            $products = array_values(array_filter(array_map(fn (int $id): ?array => $cards[$id] ?? null, $link['style_ids'])));
            if ($products !== []) {
                $result[] = ['code' => $link['code'], 'name' => $link['name'], 'products' => $products];
            }
        }

        return $result;
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, ResolvedPrice>
     */
    private function resolve(array $variantIds, int $now): array
    {
        return $variantIds === [] ? [] : $this->prices->forVariants($variantIds, new PricingContext($now));
    }

    /**
     * ATS — chỉ cho variant đã có giá (chưa có giá thì không bán được).
     *
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    private function stock(array $variantIds): array
    {
        return $variantIds === [] ? [] : $this->availability->forVariants($variantIds);
    }

    /**
     * Khoảng giá của sản phẩm (null nếu chưa có variant nào có giá → storefront không cho mua).
     *
     * @param  list<ResolvedPrice>  $prices
     * @return array<string, mixed>|null
     */
    private function range(array $prices): ?array
    {
        if ($prices === []) {
            return null;
        }

        usort($prices, fn (ResolvedPrice $a, ResolvedPrice $b): int => $a->amount->amount <=> $b->amount->amount);
        $min = $prices[0];
        $max = $prices[count($prices) - 1];

        return [
            'min' => $this->money->toArray($min->amount),
            'max' => $this->money->toArray($max->amount),
            'compare_at' => $min->compareAt === null ? null : $this->money->toArray($min->compareAt),
            'discount_percent' => max(array_map(fn (ResolvedPrice $price): int => $price->discountPercent ?? 0, $prices)) ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function price(ResolvedPrice $price): array
    {
        return [
            'amount' => $this->money->toArray($price->amount),
            'compare_at' => $price->compareAt === null ? null : $this->money->toArray($price->compareAt),
            'discount_percent' => $price->discountPercent,
        ];
    }
}
