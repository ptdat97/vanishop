<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Sửa hàng loạt sản phẩm từ danh sách Admin (stabilization A4): gán/gỡ danh mục, đổi thương hiệu, thêm/gỡ bộ sưu tập, xoá bản
 * nháp. Mỗi sản phẩm một transaction với cùng hệ quả như lưu form: tăng lock_version (form đang mở sẽ báo dữ liệu cũ), hook
 * `vani.product.after_save`, audit, event ProductUpdated (cache, chỉ mục tìm kiếm). Sản phẩm không đổi gì thì không tính.
 */
final class ProductBulkEditor
{
    public const ACTIONS = ['category_add', 'category_remove', 'brand', 'collection_add', 'collection_remove'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProductService $products,
    ) {}

    /**
     * @param  list<int>  $styleIds
     * @return int số sản phẩm đã đổi
     */
    public function apply(array $styleIds, string $action, ?int $targetId): int
    {
        $change = match ($action) {
            'category_add' => $this->addCategory($this->category($targetId)),
            'category_remove' => $this->removeCategory($this->category($targetId)),
            'brand' => $this->setBrand($targetId === null ? null : $this->brand($targetId)),
            'collection_add' => $this->addToCollection($this->collection($targetId)),
            'collection_remove' => $this->removeFromCollection($this->collection($targetId)),
            default => throw ValidationException::withMessages(['action' => __('validation.in', ['attribute' => 'action'])]),
        };

        $changed = 0;
        foreach (array_values(array_unique($styleIds)) as $styleId) {
            $changed += DB::transaction(function () use ($styleId, $change, $action, $targetId): int {
                $style = Style::query()->whereKey($styleId)->lockForUpdate()->first();
                if ($style === null || ! $change($style)) {
                    return 0;
                }
                $style->lock_version++;
                $style->save();

                Hook::action('vani.product.after_save', $style->id);
                $this->audit->record('catalog.product.bulk_edited', 'style', $style->id, ['style_code' => $style->style_code, 'action' => $action, 'target_id' => $targetId]);
                event(new ProductUpdated($style->id, $style->style_code));

                return 1;
            });
        }

        return $changed;
    }

    /**
     * Xoá các sản phẩm nháp đã chọn; sản phẩm đã mở bán/ngừng bán bỏ qua (giữ lịch sử — dùng "Ngừng bán").
     *
     * @param  list<int>  $styleIds
     * @return array{deleted: int, skipped: int}
     */
    public function deleteDrafts(array $styleIds): array
    {
        $deleted = 0;
        $skipped = 0;
        foreach (Style::query()->whereKey(array_values(array_unique($styleIds)))->get() as $style) {
            if ($style->status !== StyleStatus::Draft) {
                $skipped++;

                continue;
            }
            $this->products->deleteDraft($style);
            $deleted++;
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * @return Closure(Style): bool
     */
    private function addCategory(Category $category): Closure
    {
        return function (Style $style) use ($category): bool {
            if ($style->categories()->whereKey($category->id)->exists()) {
                return false;
            }
            $style->categories()->attach($category->id, ['position' => 0]);
            $style->primary_category_id ??= $category->id;

            return true;
        };
    }

    /**
     * @return Closure(Style): bool
     */
    private function removeCategory(Category $category): Closure
    {
        return function (Style $style) use ($category): bool {
            if ($style->categories()->detach($category->id) === 0) {
                return false;
            }
            if ($style->primary_category_id === $category->id) {
                $next = $style->categories()->orderBy('categories.id')->value('categories.id');
                $style->primary_category_id = $next === null ? null : (int) $next;
            }

            return true;
        };
    }

    /**
     * @return Closure(Style): bool
     */
    private function setBrand(?Brand $brand): Closure
    {
        return function (Style $style) use ($brand): bool {
            if ($style->brand_id === $brand?->id) {
                return false;
            }
            $style->brand_id = $brand?->id;

            return true;
        };
    }

    /**
     * @return Closure(Style): bool
     */
    private function addToCollection(ProductCollection $collection): Closure
    {
        return function (Style $style) use ($collection): bool {
            if ($style->collections()->whereKey($collection->id)->exists()) {
                return false;
            }
            $position = (int) DB::table('collection_style')->where('collection_id', $collection->id)->max('position') + 1;
            $style->collections()->attach($collection->id, ['position' => $position]);

            return true;
        };
    }

    /**
     * @return Closure(Style): bool
     */
    private function removeFromCollection(ProductCollection $collection): Closure
    {
        return fn (Style $style): bool => $style->collections()->detach($collection->id) > 0;
    }

    private function category(?int $id): Category
    {
        return ($id === null ? null : Category::query()->find($id)) ?? throw ValidationException::withMessages(['target_id' => __('catalog::messages.category_not_found')]);
    }

    private function brand(int $id): Brand
    {
        return Brand::query()->find($id) ?? throw ValidationException::withMessages(['target_id' => __('catalog::messages.brand_not_found')]);
    }

    private function collection(?int $id): ProductCollection
    {
        return ($id === null ? null : ProductCollection::query()->find($id)) ?? throw ValidationException::withMessages(['target_id' => __('catalog::messages.collection_not_found')]);
    }
}
