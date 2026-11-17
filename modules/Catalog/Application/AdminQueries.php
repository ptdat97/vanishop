<?php

declare(strict_types=1);

namespace Modules\Catalog\Application;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Shared\Domain\Text\VietnameseText;
use Modules\Shared\Http\ListSort;

/**
 * Truy vấn đọc cho màn hình Admin của Catalog — controller không tự truy vấn (architecture-consolidation D4, R9).
 */
final class AdminQueries
{
    public function mediaByChecksum(string $checksum): ?Media
    {
        return Media::query()->where('checksum', $checksum)->first();
    }

    /**
     * @return Collection<int, ProductCollection>
     */
    public function collections(): Collection
    {
        return ProductCollection::query()->with('translations')->withCount('styles')->orderBy('position')->orderBy('slug')->get();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Media> id => media
     */
    public function mediaByIds(array $ids): Collection
    {
        return Media::query()->whereKey($ids)->get()->keyBy('id');
    }

    /**
     * @return Collection<int, LinkType>
     */
    public function linkTypesWithUsage(): Collection
    {
        return LinkType::query()->with('translations')->withCount('groups')->orderBy('position')->orderBy('code')->get();
    }

    /**
     * @return Collection<int, OptionType>
     */
    public function optionTypes(): Collection
    {
        return OptionType::query()->with(['translations', 'values.translations'])->orderBy('position')->orderBy('code')->get();
    }

    public function linkType(int $id): LinkType
    {
        return LinkType::query()->findOrFail($id);
    }

    /**
     * @return Collection<int, Brand>
     */
    public function brandsWithUsage(): Collection
    {
        return Brand::query()->withCount('styles')->orderBy('position')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Attribute>
     */
    public function attributesWithUsage(): Collection
    {
        return Attribute::query()->with(['translations'])->withCount('values')->orderBy('position')->orderBy('code')->get();
    }

    public function media(int $id): ?Media
    {
        return Media::query()->find($id);
    }

    /**
     * Danh sách sản phẩm Admin: tìm theo từ khoá (không dấu), trạng thái, bộ lọc `category` (gồm danh mục con), `brand` (id hoặc
     * `none` = chưa có thương hiệu), `collection` (stabilization A4), id do bộ lọc plugin trả, sắp xếp.
     *
     * @param  array{category?: int|null, brand?: int|string|null, collection?: int|null}  $filters
     * @param  list<int>|null  $pluginIds  null = không lọc theo plugin
     * @return LengthAwarePaginator<int, Style>
     */
    public function products(string $search, ?StyleStatus $status, array $filters, ?array $pluginIds, ?ListSort $sort, int $perPage): LengthAwarePaginator
    {
        $category = $filters['category'] ?? null;
        $brand = $filters['brand'] ?? null;
        $collection = $filters['collection'] ?? null;

        return Style::query()
            ->with(['translations', 'brand:id,name', 'variants.gallery.media'])
            ->when($search !== '', function ($query) use ($search): void {
                foreach (VietnameseText::tokens($search) as $token) {
                    $query->where('search_text', 'like', '%'.addcslashes($token, '%_\\').'%');
                }
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($category !== null, fn ($query) => $query->whereIn('id', DB::table('category_style')->whereIn('category_id', $this->categoryWithDescendants((int) $category))->select('style_id')))
            ->when($brand === 'none', fn ($query) => $query->whereNull('brand_id'))
            ->when(is_int($brand), fn ($query) => $query->where('brand_id', $brand))
            ->when($collection !== null, fn ($query) => $query->whereIn('id', DB::table('collection_style')->where('collection_id', $collection)->select('style_id')))
            ->when($pluginIds !== null, fn ($query) => $query->whereIn('id', $pluginIds))
            ->when($sort, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('updated_at')->orderByDesc('id'))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return list<int> danh mục và mọi danh mục con cháu
     */
    public function categoryWithDescendants(int $categoryId): array
    {
        $ids = [$categoryId];
        $frontier = [$categoryId];
        while ($frontier !== []) {
            $frontier = array_values(array_diff(Category::query()->whereIn('parent_id', $frontier)->pluck('id')->map(fn ($id): int => (int) $id)->all(), $ids));
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    public function productExists(int $id): bool
    {
        return Style::query()->whereKey($id)->exists();
    }

    /**
     * @return Collection<int, Attribute>
     */
    public function attributes(): Collection
    {
        return Attribute::query()->with(['translations', 'values.translations'])->orderBy('position')->orderBy('code')->get();
    }

    /**
     * @return Collection<int, LinkType>
     */
    public function linkTypes(): Collection
    {
        return LinkType::query()->with('translations')->orderBy('position')->orderBy('code')->get();
    }

    /**
     * @return Collection<int, Brand>
     */
    public function brands(): Collection
    {
        return Brand::query()->orderBy('position')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Style> id => sản phẩm
     */
    public function productsByIds(array $ids): Collection
    {
        return Style::query()->with('translations')->whereKey($ids)->get()->keyBy('id');
    }
}
