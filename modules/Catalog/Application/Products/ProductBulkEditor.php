<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Persistence\Models\ProductCollection;
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
     * @param  list<int>  $productIds
     * @return int số sản phẩm đã đổi
     */
    public function apply(array $productIds, string $action, ?int $targetId): int
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
        foreach (array_values(array_unique($productIds)) as $productId) {
            $changed += DB::transaction(function () use ($productId, $change, $action, $targetId): int {
                $product = Product::query()->whereKey($productId)->lockForUpdate()->first();
                if ($product === null || ! $change($product)) {
                    return 0;
                }
                $product->lock_version++;
                $product->save();

                Hook::action('vani.product.after_save', $product->id);
                $this->audit->record('catalog.product.bulk_edited', 'product', $product->id, ['product_code' => $product->product_code, 'action' => $action, 'target_id' => $targetId]);
                event(new ProductUpdated($product->id, $product->product_code));

                return 1;
            });
        }

        return $changed;
    }

    /**
     * Xoá các sản phẩm nháp đã chọn; sản phẩm đã mở bán/ngừng bán bỏ qua (giữ lịch sử — dùng "Ngừng bán").
     *
     * @param  list<int>  $productIds
     * @return array{deleted: int, skipped: int}
     */
    public function deleteDrafts(array $productIds): array
    {
        $deleted = 0;
        $skipped = 0;
        foreach (Product::query()->whereKey(array_values(array_unique($productIds)))->get() as $product) {
            if ($product->status !== ProductStatus::Draft) {
                $skipped++;

                continue;
            }
            $this->products->deleteDraft($product);
            $deleted++;
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * @return Closure(Product): bool
     */
    private function addCategory(Category $category): Closure
    {
        return function (Product $product) use ($category): bool {
            if ($product->categories()->whereKey($category->id)->exists()) {
                return false;
            }
            $product->categories()->attach($category->id, ['position' => 0]);
            $product->primary_category_id ??= $category->id;

            return true;
        };
    }

    /**
     * @return Closure(Product): bool
     */
    private function removeCategory(Category $category): Closure
    {
        return function (Product $product) use ($category): bool {
            if ($product->categories()->detach($category->id) === 0) {
                return false;
            }
            if ($product->primary_category_id === $category->id) {
                $next = $product->categories()->orderBy('categories.id')->value('categories.id');
                $product->primary_category_id = $next === null ? null : (int) $next;
            }

            return true;
        };
    }

    /**
     * @return Closure(Product): bool
     */
    private function setBrand(?Brand $brand): Closure
    {
        return function (Product $product) use ($brand): bool {
            if ($product->brand_id === $brand?->id) {
                return false;
            }
            $product->brand_id = $brand?->id;

            return true;
        };
    }

    /**
     * @return Closure(Product): bool
     */
    private function addToCollection(ProductCollection $collection): Closure
    {
        return function (Product $product) use ($collection): bool {
            if ($product->collections()->whereKey($collection->id)->exists()) {
                return false;
            }
            $position = (int) DB::table('collection_product')->where('collection_id', $collection->id)->max('position') + 1;
            $product->collections()->attach($collection->id, ['position' => $position]);

            return true;
        };
    }

    /**
     * @return Closure(Product): bool
     */
    private function removeFromCollection(ProductCollection $collection): Closure
    {
        return fn (Product $product): bool => $product->collections()->detach($collection->id) > 0;
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
