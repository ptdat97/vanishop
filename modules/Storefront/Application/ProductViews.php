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

/**
 * Ghép dữ liệu sản phẩm cho storefront từ các module Core qua contract (Catalog + Pricing + Inventory).
 * Storefront chỉ công bố còn/hết hàng và "sắp hết", không lộ số tồn chính xác.
 * Native storefront và Storefront API cùng dùng lớp này (ADR-009).
 */
final class ProductViews
{
    public function __construct(
        private readonly CatalogReader $catalog,
        private readonly PriceResolver $prices,
        private readonly AvailabilityReader $availability,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, facets: array<string, mixed>}
     */
    public function listing(ProductFilters $filters, string $locale, int $now): array
    {
        $listing = $this->catalog->searchProducts($filters, $locale);

        $variantIds = array_merge(...array_map(fn (array $item): array => $item['variant_ids'], $listing['items'] ?: [['variant_ids' => []]]));
        $resolved = $this->resolve($variantIds, $now);
        $stock = $this->stock(array_keys($resolved));

        $listing['items'] = array_map(function (array $item) use ($resolved, $stock): array {
            $item['price'] = $this->range(array_values(array_intersect_key($resolved, array_flip($item['variant_ids']))));
            $item['in_stock'] = array_sum(array_intersect_key($stock, array_flip($item['variant_ids']))) > 0;
            unset($item['variant_ids']);

            return $item;
        }, $listing['items']);

        return $listing;
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

        $product['variants'] = array_map(fn (array $variant): array => [
            ...$variant,
            'price' => isset($resolved[$variant['id']]) ? $this->price($resolved[$variant['id']]) : null,
            'available' => ($stock[$variant['id']] ?? 0) > 0,
            'low_stock' => ($stock[$variant['id']] ?? 0) > 0 && $stock[$variant['id']] <= $lowStock,
        ], $product['variants']);
        $product['price'] = $this->range(array_values($resolved));
        $product['in_stock'] = array_sum($stock) > 0;
        unset($product['variant_ids']);

        return $product;
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
