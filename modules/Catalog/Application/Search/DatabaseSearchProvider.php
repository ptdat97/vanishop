<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionType;
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
                'color_families' => $this->colorFacet($base, $query),
                'attribute_values' => $this->attributeFacet($base, $query),
                'brands' => $this->brandFacet($base, $query),
                'sizes' => $this->sizeFacet($base, $query),
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
     * @param  'brand'|'color'|'attribute'|'size'|'price'|null  $except
     * @return Builder<Style>
     */
    private function scoped(Builder $base, ProductSearchQuery $query, ?string $except = null): Builder
    {
        $scope = clone $base;
        if ($except !== 'color') {
            $this->applyColorFilter($scope, $query->colorFamilies);
        }
        if ($except !== 'attribute') {
            $this->applyAttributeFilter($scope, $query->attributeValueIds);
        }
        if ($except !== 'brand') {
            $this->applyBrandFilter($scope, $query->brandIds);
        }
        if ($except !== 'size') {
            $this->applySizeFilter($scope, $query->sizeCodes);
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
     * Sản phẩm có biến thể đang bán thuộc một trong các size.
     *
     * @param  Builder<Style>  $builder
     * @param  list<string>  $sizeCodes
     */
    private function applySizeFilter(Builder $builder, array $sizeCodes): void
    {
        if ($sizeCodes === []) {
            return;
        }

        // Giá trị của loại tuỳ chọn `size` (ADR-045) của SKU đang bán.
        $builder->whereExists(fn ($q) => $q->select(DB::raw(1))->from('variants')
            ->join('variant_option_values as vov', 'vov.variant_id', '=', 'variants.id')
            ->join('option_values as ov', 'ov.id', '=', 'vov.option_value_id')
            ->join('option_types as ot', fn ($join) => $join->on('ot.id', '=', 'ov.option_type_id')->where('ot.code', '=', OptionType::SIZE))
            ->whereColumn('variants.style_id', 'styles.id')
            ->where('variants.status', VariantStatus::Active->value)
            ->whereIn('ov.code', $sizeCodes));
    }

    /**
     * Số sản phẩm theo mã size của biến thể đang bán (áp mọi bộ lọc trừ bộ lọc size).
     *
     * @param  Builder<Style>  $base
     * @return array<string, int>
     */
    private function sizeFacet(Builder $base, ProductSearchQuery $query): array
    {
        return DB::table('variants')
            ->join('variant_option_values as vov', 'vov.variant_id', '=', 'variants.id')
            ->join('option_values as ov', 'ov.id', '=', 'vov.option_value_id')
            ->join('option_types as ot', fn ($join) => $join->on('ot.id', '=', 'ov.option_type_id')->where('ot.code', '=', OptionType::SIZE))
            ->where('variants.status', VariantStatus::Active->value)
            ->whereIn('variants.style_id', $this->scoped($base, $query, except: 'size')->select('styles.id'))
            ->groupBy('ov.code')
            ->selectRaw('ov.code as code, count(distinct variants.style_id) as total')
            ->pluck('total', 'code')
            ->mapWithKeys(fn ($total, $code): array => [(string) $code => (int) $total])
            ->all();
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
     * @param  list<string>  $families
     */
    private function applyColorFilter(Builder $builder, array $families): void
    {
        if ($families === []) {
            return;
        }

        // Nhóm màu của giá trị kiểu ô màu mà sản phẩm dùng (ADR-045).
        $builder->whereExists(fn ($q) => $q->select(DB::raw(1))->from('style_option_values as sov')
            ->join('option_values as ov', 'ov.id', '=', 'sov.option_value_id')
            ->whereColumn('sov.style_id', 'styles.id')
            ->whereIn('ov.family', $families));
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
     * Số sản phẩm theo nhóm màu — áp mọi bộ lọc trừ bộ lọc màu (để người dùng thấy các lựa chọn khác).
     *
     * @param  Builder<Style>  $base
     * @return array<string, int>
     */
    private function colorFacet(Builder $base, ProductSearchQuery $query): array
    {
        $scope = $this->scoped($base, $query, except: 'color');

        return DB::table('style_option_values as sov')
            ->join('option_values as ov', 'ov.id', '=', 'sov.option_value_id')
            ->whereNotNull('ov.family')
            ->whereIn('sov.style_id', $scope->select('styles.id'))
            ->groupBy('ov.family')
            ->orderBy('ov.family')
            ->selectRaw('ov.family as family, count(distinct sov.style_id) as total')
            ->pluck('total', 'family')
            ->map(fn ($total): int => (int) $total)
            ->all();
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
