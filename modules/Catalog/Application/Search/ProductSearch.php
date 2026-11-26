<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Shared\Domain\Text\VietnameseText;

/**
 * Tìm kiếm sản phẩm cho storefront bằng SQL (dự án chỉ tìm bằng SQL — không có chỉ mục ngoài), trên read model
 * `product_listing` + `product_facet_values` (db-refactor §4.3, ProductListingProjector ghi): lọc danh mục (gồm danh mục con),
 * bộ sưu tập, thuộc tính, tuỳ chọn, thương hiệu, giá; facet đếm theo `(facet, token)`; sắp xếp theo index của
 * `product_listing`. Tìm chữ qua `products.search_text` (FULLTEXT ngram trên MySQL, SearchTextMatch).
 */
final class ProductSearch
{
    /** Số đếm + facet giống nhau giữa các trang/cách sắp xếp của cùng bộ lọc — cache theo phiên bản read model (L56). */
    private const COUNTS_TTL = 600;

    public function __construct(private readonly Cache $cache, private readonly ListingCacheVersion $version) {}

    public function search(ProductSearchQuery $query): ProductSearchResult
    {
        $base = $this->baseQuery($query);

        $filtered = $this->scoped($base, $query);

        // Khung giờ hiển thị phụ thuộc `now`: khoá theo phút — sản phẩm hẹn giờ vào/ra trong số đếm trễ tối đa một phút.
        $key = 'catalog:search:'.$this->version->current().':'.md5(serialize([$query->text, $query->categoryId, $query->collectionId, $query->attributeValueIds,
            $query->brandIds, $query->priceMin, $query->priceMax, $query->options, intdiv($query->now, 60)]));
        ['total' => $total, 'facets' => $facets] = $this->cache->remember($key, self::COUNTS_TTL, fn (): array => [
            'total' => (clone $filtered)->count(),
            'facets' => [
                'attribute_values' => $this->attributeFacet($base, $query),
                'brands' => $this->brandFacet($base, $query),
                'options' => $this->optionFacets($base, $query),
                'price' => $this->priceFacet($base, $query),
            ],
        ]);

        return new ProductSearchResult(
            productIds: $this->orderedIds($filtered, $query),
            total: $total,
            facets: $facets,
        );
    }

    /**
     * Chỉ id sản phẩm của trang (đúng thứ tự như `search`) — không đếm tổng, không facet. Cho khối/lưới chỉ cần thẻ sản phẩm.
     *
     * @return list<int>
     */
    public function ids(ProductSearchQuery $query): array
    {
        return $this->orderedIds($this->scoped($this->baseQuery($query), $query), $query);
    }

    /**
     * @return list<int>
     */
    private function orderedIds(Builder $filtered, ProductSearchQuery $query): array
    {
        $ordered = (clone $filtered)->select('pl.product_id');
        if ($query->categoryId !== null && $query->sort === ProductSearchQuery::SORT_NEWEST && $query->text === '') {
            // Trong trang danh mục: ưu tiên thứ tự merchandising đã ghim.
            $ordered->orderByRaw('(select min(cs.position) from category_product cs where cs.product_id = pl.product_id and cs.category_id = ?) asc', [$query->categoryId]);
        }
        match ($query->sort) {
            ProductSearchQuery::SORT_CODE => $ordered->orderBy(DB::table('products')->select('product_code')->whereColumn('products.id', 'pl.product_id')),
            // Sản phẩm chưa có giá luôn xếp cuối.
            ProductSearchQuery::SORT_PRICE_ASC => $ordered->orderByRaw('pl.list_price_min is null')->orderBy('pl.list_price_min'),
            ProductSearchQuery::SORT_PRICE_DESC => $ordered->orderByRaw('pl.list_price_min is null')->orderByDesc('pl.list_price_min'),
            default => $ordered->orderByDesc('pl.created_at'),
        };

        return array_map('intval', $ordered->orderByDesc('pl.product_id')->offset($query->offset())->limit($query->limit())->pluck('pl.product_id')->all());
    }

    private function baseQuery(ProductSearchQuery $query): Builder
    {
        $now = Carbon::createFromTimestamp($query->now);

        $builder = DB::table('product_listing as pl')
            ->where('pl.status', ProductStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('pl.published_from')->orWhere('pl.published_from', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('pl.published_to')->orWhere('pl.published_to', '>', $now));

        $tokens = VietnameseText::tokens($query->text);
        if ($tokens !== []) {
            $builder->whereIn('pl.product_id', function (Builder $products) use ($tokens): void {
                $products->select('products.id')->from('products');
                SearchTextMatch::apply($products, $tokens);
            });
        }
        if ($query->categoryId !== null) {
            $this->whereFacet($builder, 'category', [(string) $query->categoryId]);
        }
        if ($query->collectionId !== null) {
            $this->whereFacet($builder, 'collection', [(string) $query->collectionId]);
        }

        return $builder;
    }

    /**
     * Áp các nhóm lọc lên bản sao của truy vấn gốc. Facet của một nhóm bỏ chính nhóm đó (`$except`) để khách vẫn thấy
     * các lựa chọn khác trong nhóm.
     *
     * @param  string|null  $except  'brand' | 'attribute' | 'price' | 'option:<mã loại>'
     */
    private function scoped(Builder $base, ProductSearchQuery $query, ?string $except = null): Builder
    {
        $scope = clone $base;
        if ($except !== 'attribute') {
            foreach ($query->attributeValueIds as $attributeId => $valueIds) {
                if ($valueIds !== []) {
                    $this->whereFacet($scope, "attribute:{$attributeId}", array_map('strval', $valueIds));
                }
            }
        }
        if ($except !== 'brand' && $query->brandIds !== []) {
            $scope->whereIn('pl.brand_id', $query->brandIds);
        }
        foreach ($query->options as $typeCode => $tokens) {
            if ($except !== "option:{$typeCode}" && $tokens !== []) {
                $this->whereFacet($scope, "option:{$typeCode}", array_map('strval', $tokens));
            }
        }
        if ($except !== 'price') {
            if ($query->priceMin !== null) {
                $scope->where('pl.list_price_min', '>=', $query->priceMin);
            }
            if ($query->priceMax !== null) {
                $scope->where('pl.list_price_min', '<=', $query->priceMax);
            }
        }

        return $scope;
    }

    /**
     * Sản phẩm có ít nhất một token của facet (OR trong một facet).
     *
     * @param  list<string>  $tokens
     */
    private function whereFacet(Builder $builder, string $facet, array $tokens): void
    {
        $builder->whereExists(fn (Builder $q) => $q->select(DB::raw(1))->from('product_facet_values as f')
            ->whereColumn('f.product_id', 'pl.product_id')
            ->where('f.facet', $facet)
            ->whereIn('f.token', $tokens));
    }

    /**
     * Khoảng giá niêm yết của kết quả (áp mọi bộ lọc trừ bộ lọc giá); null khi không sản phẩm nào có giá.
     *
     * @return array{min: int, max: int}|null
     */
    private function priceFacet(Builder $base, ProductSearchQuery $query): ?array
    {
        $range = $this->scoped($base, $query, except: 'price')->whereNotNull('pl.list_price_min')
            ->selectRaw('min(pl.list_price_min) as lowest, max(pl.list_price_min) as highest')->first();

        return $range === null || $range->lowest === null ? null : ['min' => (int) $range->lowest, 'max' => (int) $range->highest];
    }

    /**
     * Số sản phẩm theo mã lọc của từng loại tuỳ chọn lọc được. Facet của loại đang lọc bỏ chính bộ lọc đó để khách vẫn thấy
     * các lựa chọn khác trong loại; các loại còn lại dùng chung một truy vấn.
     *
     * @return array<string, array<string, int>>
     */
    private function optionFacets(Builder $base, ProductSearchQuery $query): array
    {
        $filterable = DB::table('option_types')->where('is_filterable', true)->pluck('code')->map(fn ($code): string => (string) $code)->all();
        $filtered = array_values(array_intersect(array_map('strval', array_keys(array_filter($query->options, fn (array $tokens): bool => $tokens !== []))), $filterable));
        $facets = [];
        $count = function (Builder $scope, array $typeCodes) use (&$facets): void {
            if ($typeCodes === []) {
                return;
            }
            $rows = $this->facetCounts($scope, array_map(fn (string $code): string => "option:{$code}", $typeCodes));
            foreach ($rows as $row) {
                $facets[substr((string) $row->facet, strlen('option:'))][(string) $row->token] = (int) $row->total;
            }
        };

        $count($this->scoped($base, $query), array_values(array_diff($filterable, $filtered)));
        foreach ($filtered as $typeCode) {
            $count($this->scoped($base, $query, except: "option:{$typeCode}"), [$typeCode]);
        }

        return $facets;
    }

    /**
     * Số sản phẩm theo thương hiệu (áp mọi bộ lọc trừ bộ lọc brand).
     *
     * @return array<int, int>
     */
    private function brandFacet(Builder $base, ProductSearchQuery $query): array
    {
        return $this->scoped($base, $query, except: 'brand')
            ->whereNotNull('pl.brand_id')
            ->groupBy('pl.brand_id')
            ->selectRaw('pl.brand_id as brand_id, count(*) as total')
            ->pluck('total', 'brand_id')
            ->mapWithKeys(fn ($total, $brandId): array => [(int) $brandId => (int) $total])
            ->all();
    }

    /**
     * Số sản phẩm theo giá trị thuộc tính spec + filterable (áp mọi bộ lọc trừ bộ lọc thuộc tính).
     *
     * @return array<int, int>
     */
    private function attributeFacet(Builder $base, ProductSearchQuery $query): array
    {
        $attributes = DB::table('attributes')->where('kind', 'spec')->where('is_filterable', true)->pluck('id')
            ->map(fn ($id): string => "attribute:{$id}")->all();

        $facets = [];
        foreach ($this->facetCounts($this->scoped($base, $query, except: 'attribute'), $attributes) as $row) {
            $facets[(int) $row->token] = (int) $row->total;
        }

        return $facets;
    }

    /**
     * @param  list<string>  $facets
     * @return iterable<object{facet: string, token: string, total: int}>
     */
    private function facetCounts(Builder $scope, array $facets): iterable
    {
        if ($facets === []) {
            return [];
        }

        return DB::table('product_facet_values as fc')
            ->whereIn('fc.facet', $facets)
            ->whereIn('fc.product_id', $scope->select('pl.product_id'))
            ->groupBy('fc.facet', 'fc.token')
            ->selectRaw('fc.facet, fc.token, count(*) as total')
            ->get();
    }
}
