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

        $filtered = (clone $base);
        $this->applyColorFilter($filtered, $query->colorFamilies);
        $this->applyAttributeFilter($filtered, $query->attributeValueIds);

        $total = (clone $filtered)->count();

        $ordered = (clone $filtered)->select('styles.id');
        if ($query->categoryId !== null && $query->sort === ProductSearchQuery::SORT_NEWEST && $query->text === '') {
            // Trong trang danh mục: ưu tiên thứ tự merchandising đã ghim.
            $ordered->orderByRaw('(select min(cs.position) from category_style cs where cs.style_id = styles.id and cs.category_id = ?) asc', [$query->categoryId]);
        }
        match ($query->sort) {
            ProductSearchQuery::SORT_CODE => $ordered->orderBy('styles.style_code'),
            default => $ordered->orderByDesc('styles.created_at'),
        };
        $ids = $ordered->orderByDesc('styles.id')->offset($query->offset())->limit($query->limit())->pluck('styles.id')->all();

        return new ProductSearchResult(
            styleIds: array_map('intval', $ids),
            total: $total,
            facets: [
                'color_families' => $this->colorFacet($base, $query),
                'attribute_values' => $this->attributeFacet($base, $query),
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
            ->whereIn('styles.brand_id', $query->brandIds)
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
     * @param  Builder<Style>  $builder
     * @param  list<string>  $families
     */
    private function applyColorFilter(Builder $builder, array $families): void
    {
        if ($families === []) {
            return;
        }

        $builder->whereExists(fn ($q) => $q->select(DB::raw(1))->from('style_colors')
            ->join('colors', 'colors.id', '=', 'style_colors.color_id')
            ->whereColumn('style_colors.style_id', 'styles.id')
            ->whereIn('colors.color_family', $families));
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
        $scope = (clone $base);
        $this->applyAttributeFilter($scope, $query->attributeValueIds);

        return DB::table('style_colors')
            ->join('colors', 'colors.id', '=', 'style_colors.color_id')
            ->whereIn('style_colors.style_id', $scope->select('styles.id'))
            ->groupBy('colors.color_family')
            ->orderBy('colors.color_family')
            ->selectRaw('colors.color_family as family, count(distinct style_colors.style_id) as total')
            ->pluck('total', 'family')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Số sản phẩm theo giá trị thuộc tính spec + filterable (áp bộ lọc màu, bỏ bộ lọc thuộc tính).
     *
     * @param  Builder<Style>  $base
     * @return array<int, int>
     */
    private function attributeFacet(Builder $base, ProductSearchQuery $query): array
    {
        $scope = (clone $base);
        $this->applyColorFilter($scope, $query->colorFamilies);

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
