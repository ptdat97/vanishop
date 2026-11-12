<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Shared\Domain\Text\VietnameseText;

/**
 * Tìm kiếm trực tiếp trên MySQL (không cần index ngoài). Phù hợp dev và catalog vừa/nhỏ;
 * tìm không dấu nhờ cột styles.search_text đã chuẩn hoá.
 */
final class DatabaseSearchProvider implements SearchProvider
{
    public function code(): string
    {
        return 'database';
    }

    public function index(ProductDocument $document): void
    {
        // Đọc trực tiếp từ bảng nghiệp vụ — không có chỉ mục riêng cần cập nhật.
    }

    public function remove(int $styleId): void {}

    public function search(ProductSearchQuery $query): ProductSearchResult
    {
        $base = $this->baseQuery($query);

        $filtered = $this->scoped($base, $query);

        $total = (clone $filtered)->count();

        $ordered = (clone $filtered)->select('styles.id');
        if ($query->categoryId !== null && $query->sort === ProductSearchQuery::SORT_NEWEST && $query->text === '') {
            // Trong trang danh mục: ưu tiên thứ tự merchandising đã ghim.
            $ordered->orderByRaw('(select min(cs.position) from category_style cs where cs.style_id = styles.id and cs.category_id = ?) asc', [$query->categoryId]);
        }
        match ($query->sort) {
            ProductSearchQuery::SORT_CODE => $ordered->orderBy('styles.style_code'),
            // Sản phẩm chưa có giá luôn xếp cuối.
            ProductSearchQuery::SORT_PRICE_ASC => $ordered->orderByRaw('styles.list_price_min is null')->orderBy('styles.list_price_min'),
            ProductSearchQuery::SORT_PRICE_DESC => $ordered->orderByRaw('styles.list_price_min is null')->orderByDesc('styles.list_price_min'),
            default => $ordered->orderByDesc('styles.created_at'),
        };
        $ids = $ordered->orderByDesc('styles.id')->offset($query->offset())->limit($query->limit())->pluck('styles.id')->all();

        return new ProductSearchResult(
            styleIds: array_map('intval', $ids),
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
     * @return Builder<Style>
     */
    private function baseQuery(ProductSearchQuery $query): Builder
    {
        $now = Carbon::createFromTimestamp($query->now);

        $builder = Style::query()
            ->where('styles.status', StyleStatus::Active)
            ->where(fn (Builder $q) => $q->whereNull('styles.published_from')->orWhere('styles.published_from', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('styles.published_to')->orWhere('styles.published_to', '>', $now));

        foreach (VietnameseText::tokens($query->text) as $token) {
            $builder->where('styles.search_text', 'like', '%'.addcslashes($token, '%_\\').'%');
        }

        if ($query->categoryId !== null) {
            $path = Category::query()->whereKey($query->categoryId)->value('path');
            $builder->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('category_style')
                ->join('categories', 'categories.id', '=', 'category_style.category_id')
                ->whereColumn('category_style.style_id', 'styles.id')
                ->where('categories.path', 'like', ($path ?? '/-1/').'%'));
        }

        if ($query->collectionId !== null) {
            $builder->whereExists(fn ($q) => $q->select(DB::raw(1))->from('collection_style')
                ->whereColumn('collection_style.style_id', 'styles.id')
                ->where('collection_style.collection_id', $query->collectionId));
        }

        return $builder;
    }

    /**
     * Áp các nhóm lọc lên bản sao của truy vấn gốc. Facet của một nhóm bỏ chính nhóm đó (`$except`) để khách vẫn thấy
     * các lựa chọn khác trong nhóm.
     *
     * @param  Builder<Style>  $base
     * @param  string|null  $except  'brand' | 'attribute' | 'price' | 'option:<mã loại>'
     * @return Builder<Style>
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
                $scope->where('styles.list_price_min', '>=', $query->priceMin);
            }
            if ($query->priceMax !== null) {
                $scope->where('styles.list_price_min', '<=', $query->priceMax);
            }
        }

        return $scope;
    }

    /**
     * Khoảng giá niêm yết của kết quả (áp mọi bộ lọc trừ bộ lọc giá); null khi không sản phẩm nào có giá.
     *
     * @param  Builder<Style>  $base
     * @return array{min: int, max: int}|null
     */
    private function priceFacet(Builder $base, ProductSearchQuery $query): ?array
    {
        $range = $this->scoped($base, $query, except: 'price')->whereNotNull('styles.list_price_min')
            ->selectRaw('min(styles.list_price_min) as lowest, max(styles.list_price_min) as highest')->toBase()->first();

        return $range === null || $range->lowest === null ? null : ['min' => (int) $range->lowest, 'max' => (int) $range->highest];
    }

    /**
     * Sản phẩm có SKU đang bán mang giá trị của loại tuỳ chọn với mã lọc thuộc danh sách (OR trong một loại).
     *
     * @param  Builder<Style>  $builder
     * @param  list<string>  $tokens
     */
    private function applyOptionFilter(Builder $builder, string $typeCode, array $tokens): void
    {
        if ($tokens === []) {
            return;
        }

        $builder->whereExists(fn ($q) => $this->activeOptionValues($q)
            ->whereColumn('variants.style_id', 'styles.id')
            ->where('ot.code', $typeCode)
            ->whereIn(DB::raw(OptionFilterToken::SQL), $tokens));
    }

    /**
     * Số sản phẩm theo mã lọc của từng loại tuỳ chọn lọc được. Facet của loại đang lọc bỏ chính bộ lọc đó để khách vẫn thấy
     * các lựa chọn khác trong loại; các loại còn lại dùng chung một truy vấn.
     *
     * @param  Builder<Style>  $base
     * @return array<string, array<string, int>>
     */
    private function optionFacets(Builder $base, ProductSearchQuery $query): array
    {
        $filtered = array_keys(array_filter($query->options, fn (array $tokens): bool => $tokens !== []));
        $facets = [];
        $count = function (Builder $scope, ?string $only, array $skip) use (&$facets): void {
            $rows = $this->activeOptionValues(DB::table('variants'))
                ->where('ot.is_filterable', true)
                ->whereIn('variants.style_id', $scope->select('styles.id'))
                ->when($only !== null, fn ($q) => $q->where('ot.code', $only))
                ->when($only === null && $skip !== [], fn ($q) => $q->whereNotIn('ot.code', $skip))
                ->groupBy('ot.code', DB::raw(OptionFilterToken::SQL))
                ->selectRaw('ot.code as type_code, '.OptionFilterToken::SQL.' as token, count(distinct variants.style_id) as total')
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
     * @param  Builder<Style>  $builder
     * @param  list<int>  $brandIds
     */
    private function applyBrandFilter(Builder $builder, array $brandIds): void
    {
        if ($brandIds !== []) {
            $builder->whereIn('styles.brand_id', $brandIds);
        }
    }

    /**
     * Số sản phẩm theo thương hiệu (áp mọi bộ lọc trừ bộ lọc brand).
     *
     * @param  Builder<Style>  $base
     * @return array<int, int>
     */
    private function brandFacet(Builder $base, ProductSearchQuery $query): array
    {
        $scope = $this->scoped($base, $query, except: 'brand');

        return $scope->whereNotNull('styles.brand_id')
            ->groupBy('styles.brand_id')
            ->selectRaw('styles.brand_id as brand_id, count(*) as total')
            ->toBase()
            ->pluck('total', 'brand_id')
            ->mapWithKeys(fn ($total, $brandId): array => [(int) $brandId => (int) $total])
            ->all();
    }

    /**
     * @param  Builder<Style>  $builder
     * @param  array<int, list<int>>  $valuesByAttribute
     */
    private function applyAttributeFilter(Builder $builder, array $valuesByAttribute): void
    {
        foreach ($valuesByAttribute as $valueIds) {
            if ($valueIds === []) {
                continue;
            }
            $builder->whereExists(fn ($q) => $q->select(DB::raw(1))->from('style_attribute_values')
                ->whereColumn('style_attribute_values.style_id', 'styles.id')
                ->whereIn('style_attribute_values.attribute_value_id', $valueIds));
        }
    }

    /**
     * Số sản phẩm theo giá trị thuộc tính spec + filterable (áp mọi bộ lọc trừ bộ lọc thuộc tính).
     *
     * @param  Builder<Style>  $base
     * @return array<int, int>
     */
    private function attributeFacet(Builder $base, ProductSearchQuery $query): array
    {
        $scope = $this->scoped($base, $query, except: 'attribute');

        return DB::table('style_attribute_values')
            ->join('attributes', 'attributes.id', '=', 'style_attribute_values.attribute_id')
            ->where('attributes.kind', 'spec')
            ->where('attributes.is_filterable', true)
            ->whereNotNull('style_attribute_values.attribute_value_id')
            ->whereIn('style_attribute_values.style_id', $scope->select('styles.id'))
            ->groupBy('style_attribute_values.attribute_value_id')
            ->selectRaw('style_attribute_values.attribute_value_id as value_id, count(distinct style_attribute_values.style_id) as total')
            ->pluck('total', 'value_id')
            ->mapWithKeys(fn ($total, $valueId): array => [(int) $valueId => (int) $total])
            ->all();
    }
}
