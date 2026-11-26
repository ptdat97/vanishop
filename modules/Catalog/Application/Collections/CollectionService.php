<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Collections;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\Search\ProductListingProjector;
use Modules\Catalog\Application\StaleRecord;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Bộ sưu tập thủ công: danh sách sản phẩm theo thứ tự nhập.
 */
final class CollectionService
{
    public function __construct(private readonly AuditLogger $audit, private readonly MediaDirectory $media, private readonly ProductListingProjector $listing) {}

    /** Chủ sở hữu ghi nhận ảnh bìa đang dùng (Thư viện ảnh không cho xoá). */
    public const MEDIA_OWNER = 'collection';

    /**
     * @param  array{slug: string, status: string, position: int, image_media_id?: int|null, translations: array<string, array{name: string, description?: string|null, meta_title?: string|null, meta_description?: string|null}>}  $data
     * @param  list<string>  $productCodes  thứ tự hiển thị
     * @param  int|null  $expectedLockVersion  phiên bản form đã mở (sửa); khác phiên bản hiện tại → StaleRecord
     */
    public function save(array $data, array $productCodes, ?ProductCollection $collection = null, ?int $expectedLockVersion = null): ProductCollection
    {
        $codes = array_values(array_unique(array_filter(array_map('trim', $productCodes))));
        $products = Product::query()->whereIn('product_code', $codes)->pluck('id', 'product_code');

        $missing = array_values(array_diff($codes, $products->keys()->all()));
        if ($missing !== []) {
            throw ValidationException::withMessages(['product_codes' => __('catalog::messages.product_codes_not_found', ['codes' => implode(', ', $missing)])]);
        }

        return DB::transaction(function () use ($data, $codes, $products, $collection, $expectedLockVersion): ProductCollection {
            if ($collection !== null && $expectedLockVersion !== null) {
                $updated = ProductCollection::query()->whereKey($collection->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
                if ($updated === 0) {
                    throw new StaleRecord;
                }
                $collection->lock_version = $expectedLockVersion + 1;
                $collection->syncOriginalAttribute('lock_version');
            }
            $collection ??= new ProductCollection;
            $collection->fill(['slug' => $data['slug'], 'status' => $data['status'], 'position' => $data['position']]);
            if (array_key_exists('image_media_id', $data)) {
                $collection->image_media_id = $data['image_media_id'];
            }
            $collection->save();
            $collection->syncTranslations($data['translations']);
            if (array_key_exists('image_media_id', $data)) {
                $this->media->syncUsages(self::MEDIA_OWNER, $collection->id, 'image', $collection->image_media_id === null ? [] : [$collection->image_media_id]);
            }

            $before = $collection->products()->pluck('products.id')->all();
            $sync = [];
            foreach ($codes as $position => $code) {
                $sync[(int) $products[$code]] = ['position' => $position];
            }
            $collection->products()->sync($sync);

            $this->audit->record($collection->wasRecentlyCreated ? 'catalog.collection.created' : 'catalog.collection.updated', 'collection', $collection->id, ['slug' => $collection->slug]);

            // Sản phẩm vào/ra bộ sưu tập cần cập nhật read model trang danh sách (facet bộ sưu tập) và cache.
            $touched = array_values(array_unique([...$before, ...array_keys($sync)]));
            foreach (Product::query()->whereIn('id', $touched)->get(['id', 'product_code']) as $product) {
                event(new ProductUpdated($product->id, $product->product_code));
            }
            $this->listing->projectLater($touched);

            return $collection;
        });
    }

    public function delete(ProductCollection $collection): void
    {
        DB::transaction(function () use ($collection): void {
            $collection = ProductCollection::query()->lockForUpdate()->findOrFail($collection->id);
            $products = Product::query()->whereIn('id', $collection->products()->pluck('products.id'))->get();
            $this->media->releaseUsages(self::MEDIA_OWNER, $collection->id);
            $collection->delete();
            $this->audit->record('catalog.collection.deleted', 'collection', $collection->id, ['slug' => $collection->slug]);

            foreach ($products as $product) {
                event(new ProductUpdated($product->id, $product->product_code));
            }
            $this->listing->projectLater($products->pluck('id')->map(fn ($id): int => (int) $id)->all());
        });
    }
}
