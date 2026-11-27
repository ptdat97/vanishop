<?php

declare(strict_types=1);

namespace Modules\Catalog\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductListingProjected;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Pricing\Events\PriceChanged;
use Modules\Shared\Contracts\PageCacheInvalidator;
use Modules\Shared\Contracts\PageCacheTags;

/**
 * Event của sản phẩm → tag cache trang cần xoá (ADR-051 §4), gom theo lô:
 *
 * | Event | Tag |
 * |---|---|
 * | `ProductListingProjected`, `ProductUpdated`, `ProductArchived`, `PriceChanged` | `product:{id}`, `category:{id}` (gồm cha), `collection:{id}`, `brand:{id}` chứa sản phẩm, `catalog:listing` |
 * | `AvailabilityChanged` | `product:{id}` (trang sản phẩm và mọi thẻ của sản phẩm) |
 *
 * Danh mục, thương hiệu, bộ sưu tập, thuộc tính, tuỳ chọn đổi → `catalog:taxonomy` (model event, CatalogServiceProvider).
 */
final class QueuePageCacheInvalidation
{
    public function __construct(private readonly PageCacheInvalidator $invalidator) {}

    public function handle(ProductListingProjected|ProductUpdated|ProductArchived|PriceChanged|AvailabilityChanged $event): void
    {
        match (true) {
            $event instanceof ProductListingProjected => $this->listingChanged($event->productIds),
            $event instanceof ProductUpdated, $event instanceof ProductArchived => $this->listingChanged([$event->productId]),
            $event instanceof PriceChanged => $this->listingChanged($this->productsOf($event->variantIds)),
            $event instanceof AvailabilityChanged => $this->invalidator->invalidate(...$this->productTags($this->productsOf($event->variantIds))),
        };
    }

    /**
     * @param  list<int>  $productIds
     */
    private function listingChanged(array $productIds): void
    {
        if ($productIds === []) {
            return;
        }
        $scopes = DB::table('product_facet_values')->whereIn('product_id', $productIds)->whereIn('facet', ['category', 'collection'])
            ->distinct()->get(['facet', 'token'])->map(fn (object $row): string => $row->facet.':'.$row->token)->all();
        $brands = DB::table('product_listing')->whereIn('product_id', $productIds)->whereNotNull('brand_id')->distinct()
            ->pluck('brand_id')->map(fn ($id): string => 'brand:'.$id)->all();

        $this->invalidator->invalidate(PageCacheTags::CATALOG_LISTING, ...$this->productTags($productIds), ...$scopes, ...$brands);
    }

    /**
     * @param  list<int>  $variantIds
     * @return list<int>
     */
    private function productsOf(array $variantIds): array
    {
        return $variantIds === [] ? [] : DB::table('variants')->whereIn('id', $variantIds)->distinct()->pluck('product_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  list<int>  $productIds
     * @return list<string>
     */
    private function productTags(array $productIds): array
    {
        return array_map(fn (int $id): string => 'product:'.$id, $productIds);
    }
}
