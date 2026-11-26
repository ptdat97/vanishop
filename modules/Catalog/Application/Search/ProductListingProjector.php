<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Closure;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Events\ProductListingProjected;

/**
 * Ghi read model của trang danh sách (db-refactor §4.3): `product_listing` (một dòng mỗi sản phẩm — trạng thái, khung giờ,
 * thương hiệu, ngày tạo) và `product_facet_values` (danh mục gồm danh mục cha, bộ sưu tập, giá trị thuộc tính, mã lọc của
 * giá trị tuỳ chọn trên SKU đang bán). Use case của Catalog gọi `project()` trong transaction của mình; `deferring()` gom
 * nhiều lần gọi (ma trận SKU, nhập file) thành một lần dựng cuối.
 *
 * Không đụng `list_price_min` (Pricing ghi qua ListPrices) và `in_stock` (Inventory ghi qua ListingAvailability): dựng lại
 * giữ nguyên hai cột này. Sau khi dựng phát `ProductListingProjected` (sau commit) để Inventory tính lại `in_stock`.
 */
final class ProductListingProjector
{
    private const CHUNK = 500;

    /** @var array<int, true>|null id chờ dựng khi đang trong deferring() */
    private ?array $pending = null;

    /**
     * @param  int|list<int>  $productIds
     */
    public function project(int|array $productIds): void
    {
        $ids = array_values(array_unique(array_map('intval', (array) $productIds)));
        if ($ids === []) {
            return;
        }
        if ($this->pending !== null) {
            $this->pending += array_fill_keys($ids, true);

            return;
        }
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            $this->rebuild($chunk);
        }
        app(ListingCacheVersion::class)->bump();
        event(new ProductListingProjected($ids));
    }

    /**
     * Như `project()` cho thay đổi có thể chạm rất nhiều sản phẩm: tối đa một lô dựng ngay (trong transaction hiện tại),
     * nhiều hơn thì đẩy RebuildProductListingJob theo lô sau commit — trang danh sách cập nhật trễ vài giây.
     *
     * @param  list<int>  $productIds
     */
    public function projectLater(array $productIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        if (count($ids) <= self::CHUNK) {
            $this->project($ids);

            return;
        }
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            RebuildProductListingJob::dispatch($chunk)->afterCommit();
        }
    }

    /**
     * Chạy `$work`, gom mọi `project()` bên trong thành một lần dựng khi `$work` xong (lồng nhau: lớp ngoài cùng dựng).
     *
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    public function deferring(Closure $work): mixed
    {
        if ($this->pending !== null) {
            return $work();
        }
        $this->pending = [];
        try {
            $result = $work();
            $ids = array_keys($this->pending);
        } finally {
            $this->pending = null;
        }
        $this->project($ids);

        return $result;
    }

    /**
     * Sản phẩm có SKU mang giá trị của một loại tuỳ chọn (đổi mã/kiểu hiển thị của loại).
     *
     * @return list<int>
     */
    public function productsUsingOptionType(int $optionTypeId): array
    {
        return DB::table('product_options')->where('option_type_id', $optionTypeId)->distinct()->pluck('product_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @return list<int>
     */
    public function productsUsingOptionValue(int $optionValueId): array
    {
        return DB::table('variant_option_values')->join('variants', 'variants.id', '=', 'variant_option_values.variant_id')
            ->where('variant_option_values.option_value_id', $optionValueId)->distinct()->pluck('variants.product_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Sản phẩm gắn trực tiếp với danh mục (hoặc cây con) — facet danh mục lưu cả danh mục cha nên đổi cây phải dựng lại.
     *
     * @param  list<int>  $categoryIds
     * @return list<int>
     */
    public function productsInCategories(array $categoryIds): array
    {
        return $categoryIds === [] ? [] : DB::table('category_product')->whereIn('category_id', $categoryIds)->distinct()->pluck('product_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  list<int>  $ids
     */
    private function rebuild(array $ids): void
    {
        $products = DB::table('products')->whereIn('id', $ids)->get(['id', 'status', 'published_from', 'published_to', 'brand_id', 'created_at']);
        $found = $products->pluck('id')->map(fn ($id): int => (int) $id)->all();

        DB::table('product_facet_values')->whereIn('product_id', $ids)->delete();
        if (($gone = array_diff($ids, $found)) !== []) {
            DB::table('product_listing')->whereIn('product_id', $gone)->delete();
        }
        if ($found === []) {
            return;
        }

        $now = now();
        DB::table('product_listing')->upsert($products->map(fn (object $product): array => [
            'product_id' => (int) $product->id,
            'status' => (string) $product->status,
            'published_from' => $product->published_from,
            'published_to' => $product->published_to,
            'brand_id' => $product->brand_id === null ? null : (int) $product->brand_id,
            'created_at' => $product->created_at ?? $now,
        ])->all(), ['product_id'], ['status', 'published_from', 'published_to', 'brand_id', 'created_at']);

        $rows = [];
        $add = function (string $facet, string $token, int $productId) use (&$rows): void {
            $rows["{$facet}\0{$token}\0{$productId}"] = ['facet' => $facet, 'token' => $token, 'product_id' => $productId];
        };
        foreach (DB::table('category_product')->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->whereIn('category_product.product_id', $found)->get(['category_product.product_id', 'categories.path']) as $row) {
            foreach (array_filter(explode('/', (string) $row->path)) as $categoryId) {
                $add('category', $categoryId, (int) $row->product_id);
            }
        }
        foreach (DB::table('collection_product')->whereIn('product_id', $found)->get(['product_id', 'collection_id']) as $row) {
            $add('collection', (string) $row->collection_id, (int) $row->product_id);
        }
        foreach (DB::table('product_attribute_values')->whereIn('product_id', $found)->whereNotNull('attribute_value_id')
            ->get(['product_id', 'attribute_id', 'attribute_value_id']) as $row) {
            $add("attribute:{$row->attribute_id}", (string) $row->attribute_value_id, (int) $row->product_id);
        }
        foreach (DB::table('variants')
            ->join('variant_option_values as vov', 'vov.variant_id', '=', 'variants.id')
            ->join('option_values as ov', 'ov.id', '=', 'vov.option_value_id')
            ->join('option_types as ot', 'ot.id', '=', 'vov.option_type_id')
            ->whereIn('variants.product_id', $found)->where('variants.status', VariantStatus::Active->value)
            ->whereNotNull('ov.filter_token')->where('ov.filter_token', '!=', '')
            ->distinct()->get(['variants.product_id', 'ot.code', 'ov.filter_token']) as $row) {
            $add("option:{$row->code}", (string) $row->filter_token, (int) $row->product_id);
        }
        foreach (array_chunk(array_values($rows), 1000) as $chunk) {
            DB::table('product_facet_values')->insert($chunk);
        }
    }
}
