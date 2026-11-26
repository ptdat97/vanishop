<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Shared\Domain\Text\VietnameseText;

/**
 * Tìm kiếm sản phẩm cho storefront bằng truy vấn SQL (dự án chỉ tìm bằng SQL — không có chỉ mục ngoài): tìm không dấu
 * qua cột `products.search_text` (ProductService ghi khi lưu), lọc danh mục/thuộc tính/tuỳ chọn/giá, facet, sắp xếp.
 * Đo 10.000 sản phẩm / 50.000 SKU: 5–16 ms mỗi truy vấn (2026-11-20).
 */
final class ProductSearch
{
    public function search(ProductSearchQuery $query): ProductSearchResult
    {
        $base = $this->baseQuery($query);

        $filtered = $this->scoped($base, $query);

        $total = (clone $filtered)->count();

        $ordered = (clone $filtered)->select('products.id');
        if ($query->categoryId !== null && $query->sort === ProductSearchQuery::SORT_NEWEST && $query->text === '') {
            // Trong trang danh mục: ưu tiên thứ tự merchandising đã ghim.
            $ordered->orderByRaw('(select min(cs.position) from category_product cs where cs.product_id = products.id and cs.category_id = ?) asc', [$query->categoryId]);
        }
        match ($query->sort) {
            ProductSearchQuery::SORT_CODE => $ordered->orderBy('products.product_code'),
            // Sản phẩm chưa có giá luôn xếp cuối.
            ProductSearchQuery::SORT_PRICE_ASC => $ordered->orderByRaw('products.list_price_min is null')->orderBy('products.list_price_min'),
            ProductSearchQuery::SORT_PRICE_DESC => $ordered->orderByRaw('products.list_price_min is null')->orderByDesc('products.list_price_min'),
            default => $ordered->orderByDesc('products.created_at'),
        };
        $ids = $ordered->orderByDesc('products.id')->offset($query->offset())->limit($query->limit())->pluck('products.id')->all();

        return new ProductSearchResult(
            productIds: array_map('intval', $ids),
            total: $total,
            facets: [
                'attribute_values' => $this->attributeFacet($base, $query),
                'brands' => $this->brandFacet($base, $query),
                'options' => $this->optionFacets($base, $query),
                'price' => $this->priceFacet($base, $query),
            ],
        );
    }

    /**
     * @return Builder<Product>
     */
    private function baseQuery(ProductSearchQuery $query): Builder
    {
        $now = Carbon::createFromTimestamp($query->now);

        $builder = Product::query()
            ->where('products.status', ProductStatus::Active)
            ->where(fn (Builder $q) => $q->whereNull('products.published_from')->orWhere('products.published_from', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('products.published_to')->orWhere('products.published_to', '>', $now));

        foreach (VietnameseText::tokens($query->text) as $token) {
            $builder->where('products.search_text', 'like', '%'.addcslashes($token, '%_\\').'%');
        }

        if ($query->categoryId !== null) {
            $path = Category::query()->whereKey($query->categoryId)->value('path');
            $builder->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('category_product')
                ->join('categories', 'categories.id', '=', 'category_product.category_id')
                ->whereColumn('category_product.product_id', 'products.id')
                ->where('categories.path', 'like', ($path ?? '/-1/').'%'));
        }

        if ($query->collectionId !== null) {
            $builder->whereExists(fn ($q) => $q->select(DB::raw(1))->from('collection_product')
                ->whereColumn('collection_product.product_id', 'products.id')
                ->where('collection_product.collection_id', $query->collectionId));
        }

        return $builder;
    }

    /**
     * Áp các nhóm lọc lên bản sao của truy vấn gốc. Facet của một nhóm bỏ chính nhóm đó (`$except`) để khách vẫn thấy
     * các lựa chọn khác trong nhóm.
     *
     * @param  Builder<Product>  $base
     * @param  string|null  $except  'brand' | 'attribute' | 'price' | 'option:<mã loại>'
     * @return Builder<Product>
     */
    private function scoped(Builder $base, ProductSearchQuery $query, ?string $except = null): Builder
    {
        $scope = clone $base;
        if ($except !== 'attribute') {
            $this->applyAttributeFilter($scope, $query->attributeValueIds);
        }
        if ($except !== 'brand') {
            $this->applyBrandFilter($scope, $query->brandIds);
        }
        foreach ($query->options as $typeCode => $tokens) {
            if ($except !== "option:{$typeCode}") {
                $this->applyOptionFilter($scope, (string) $typeCode, $tokens);
            }
        }
        if ($except !== 'price') {
            if ($query->priceMin !== null) {
                $scope->where('products.list_price_min', '>=', $query->priceMin);
            }
            if ($query->priceMax !== null) {
                $scope->where('products.list_price_min', '<=', $query->priceMax);
            }
        }

        return $scope;
    }

    /**
     * Khoảng giá niêm yết của kết quả (áp mọi bộ lọc trừ bộ lọc giá); null khi không sản phẩm nào có giá.
     *
     * @param  Builder<Product>  $base
     * @return array{min: int, max: int}|null
     */
    private function priceFacet(Builder $base, ProductSearchQuery $query): ?array
    {
        $range = $this->scoped($base, $query, except: 'price')->whereNotNull('products.list_price_min')
            ->selectRaw('min(products.list_price_min) as lowest, max(products.list_price_min) as highest')->toBase()->first();

        return $range === null || $range->lowest === null ? null : ['min' => (int) $range->lowest, 'max' => (int) $range->highest];
    }

    /**
     * Sản phẩm có SKU đang bán mang giá trị của loại tuỳ chọn với mã lọc thuộc danh sách (OR trong một loại).
     *
     * @param  Builder<Product>  $builder
     * @param  list<string>  $tokens
     */
    private function applyOptionFilter(Builder $builder, string $typeCode, array $tokens): void
    {
        if ($tokens === []) {
            return;
        }

        $builder->whereExists(fn ($q) => $this->activeOptionValues($q)
            ->whereColumn('variants.product_id', 'products.id')
            ->where('ot.code', $typeCode)
            ->whereIn(DB::raw(OptionFilterToken::SQL), $tokens));
    }

    /**
     * Số sản phẩm theo mã lọc của từng loại tuỳ chọn lọc được. Facet của loại đang lọc bỏ chính bộ lọc đó để khách vẫn thấy
     * các lựa chọn khác trong loại; các loại còn lại dùng chung một truy vấn.
     *
     * @param  Builder<Product>  $base
     * @return array<string, array<string, int>>
     */
    private function optionFacets(Builder $base, ProductSearchQuery $query): array
    {
        $filtered = array_keys(array_filter($query->options, fn (array $tokens): bool => $tokens !== []));
        $facets = [];
        $count = function (Builder $scope, ?string $only, array $skip) use (&$facets): void {
            $rows = $this->activeOptionValues(DB::table('variants'))
                ->where('ot.is_filterable', true)
                ->whereIn('variants.product_id', $scope->select('products.id'))
                ->when($only !== null, fn ($q) => $q->where('ot.code', $only))
                ->when($only === null && $skip !== [], fn ($q) => $q->whereNotIn('ot.code', $skip))
                ->groupBy('ot.code', DB::raw(OptionFilterToken::SQL))
                ->selectRaw('ot.code as type_code, '.OptionFilterToken::SQL.' as token, count(distinct variants.product_id) as total')
                ->get();
            foreach ($rows as $row) {
                if ($row->token !== null && $row->token !== '') {
                    $facets[(string) $row->type_code][(string) $row->token] = (int) $row->total;
                }
            }
        };

        $count($this->scoped($base, $query), null, array_map('strval', $filtered));
        foreach ($filtered as $typeCode) {
            $count($this->scoped($base, $query, except: "option:{$typeCode}"), (string) $typeCode, []);
        }

        return $facets;
    }

    /**
     * SKU đang bán nối với giá trị và loại tuỳ chọn (bí danh `ov`, `ot`); truy vấn con rỗng (`whereExists`) được gắn `from variants`.
     */
    private function activeOptionValues(QueryBuilder $query): QueryBuilder
    {
        if ($query->from === null) {
            $query->select(DB::raw(1))->from('variants');
        }

        return $query->join('variant_option_values as vov', 'vov.variant_id', '=', 'variants.id')
            ->join('option_values as ov', 'ov.id', '=', 'vov.option_value_id')
            ->join('option_types as ot', 'ot.id', '=', 'vov.option_type_id')
            ->where('variants.status', VariantStatus::Active->value);
    }

    /**
     * @param  Builder<Product>  $builder
     * @param  list<int>  $brandIds
     */
    private function applyBrandFilter(Builder $builder, array $brandIds): void
    {
        if ($brandIds !== []) {
            $builder->whereIn('products.brand_id', $brandIds);
        }
    }

    /**
     * Số sản phẩm theo thương hiệu (áp mọi bộ lọc trừ bộ lọc brand).
     *
     * @param  Builder<Product>  $base
     * @return array<int, int>
     */
    private function brandFacet(Builder $base, ProductSearchQuery $query): array
    {
        $scope = $this->scoped($base, $query, except: 'brand');

        return $scope->whereNotNull('products.brand_id')
            ->groupBy('products.brand_id')
            ->selectRaw('products.brand_id as brand_id, count(*) as total')
            ->toBase()
            ->pluck('total', 'brand_id')
            ->mapWithKeys(fn ($total, $brandId): array => [(int) $brandId => (int) $total])
            ->all();
    }

    /**
     * @param  Builder<Product>  $builder
     * @param  array<int, list<int>>  $valuesByAttribute
     */
    private function applyAttributeFilter(Builder $builder, array $valuesByAttribute): void
    {
        foreach ($valuesByAttribute as $valueIds) {
            if ($valueIds === []) {
                continue;
            }
            $builder->whereExists(fn ($q) => $q->select(DB::raw(1))->from('product_attribute_values')
                ->whereColumn('product_attribute_values.product_id', 'products.id')
                ->whereIn('product_attribute_values.attribute_value_id', $valueIds));
        }
    }

    /**
     * Số sản phẩm theo giá trị thuộc tính spec + filterable (áp mọi bộ lọc trừ bộ lọc thuộc tính).
     *
     * @param  Builder<Product>  $base
     * @return array<int, int>
     */
    private function attributeFacet(Builder $base, ProductSearchQuery $query): array
    {
        $scope = $this->scoped($base, $query, except: 'attribute');

        return DB::table('product_attribute_values')
            ->join('attributes', 'attributes.id', '=', 'product_attribute_values.attribute_id')
            ->where('attributes.kind', 'spec')
            ->where('attributes.is_filterable', true)
            ->whereNotNull('product_attribute_values.attribute_value_id')
            ->whereIn('product_attribute_values.product_id', $scope->select('products.id'))
            ->groupBy('product_attribute_values.attribute_value_id')
            ->selectRaw('product_attribute_values.attribute_value_id as value_id, count(distinct product_attribute_values.product_id) as total')
            ->pluck('total', 'value_id')
            ->mapWithKeys(fn ($total, $valueId): array => [(int) $valueId => (int) $total])
            ->all();
    }
}
