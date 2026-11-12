<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Ảnh theo SKU (ADR-045): tải lên, gắn từ Thư viện ảnh, sắp xếp, xoá; áp bộ ảnh của một SKU cho mọi SKU cùng giá trị của
 * một trục (vd. cùng màu) để không phải tải lại cho từng size.
 */
final class VariantImages
{
    public const MAX_IMAGES = 20;

    public function __construct(
        private readonly MediaLibrary $media,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function addFiles(Style $style, Variant $variant, array $files): void
    {
        $this->guardCount($variant, count($files));
        DB::transaction(function () use ($style, $variant, $files): void {
            $position = (int) $variant->gallery()->max('position');
            foreach ($files as $file) {
                $variant->gallery()->create(['media_id' => $this->media->store($file)->id, 'role' => 'gallery', 'position' => ++$position]);
            }
            $this->touch($style, 'catalog.product.images_added', ['variant_id' => $variant->id, 'count' => count($files)]);
        });
    }

    /**
     * Gắn ảnh có sẵn trong Thư viện ảnh (bỏ qua ảnh SKU này đã có).
     *
     * @param  list<int>  $mediaIds
     */
    public function attachMedia(Style $style, Variant $variant, array $mediaIds): void
    {
        $mediaIds = array_values(array_unique($mediaIds));
        if (Media::query()->whereKey($mediaIds)->count() !== count($mediaIds)) {
            throw ValidationException::withMessages(['images' => __('catalog::messages.media_not_found')]);
        }
        $new = array_values(array_diff($mediaIds, $variant->gallery()->pluck('media_id')->all()));
        if ($new === []) {
            return;
        }
        $this->guardCount($variant, count($new));
        DB::transaction(function () use ($style, $variant, $new): void {
            $position = (int) $variant->gallery()->max('position');
            foreach ($new as $mediaId) {
                $variant->gallery()->create(['media_id' => $mediaId, 'role' => 'gallery', 'position' => ++$position]);
            }
            $this->touch($style, 'catalog.product.images_added', ['variant_id' => $variant->id, 'count' => count($new), 'source' => 'library']);
        });
    }

    public function remove(Style $style, Variant $variant, Mediable $image): void
    {
        $image->delete();
        $this->touch($style, 'catalog.product.image_removed', ['variant_id' => $variant->id]);
    }

    /**
     * @param  list<int>  $mediableIds  thứ tự mới
     */
    public function reorder(Style $style, Variant $variant, array $mediableIds): void
    {
        DB::transaction(function () use ($style, $variant, $mediableIds): void {
            $current = $variant->gallery()->pluck('id')->all();
            if (array_diff($mediableIds, $current) !== [] || count($mediableIds) !== count($current)) {
                throw ValidationException::withMessages(['order' => __('catalog::messages.image_order_invalid')]);
            }
            foreach ($mediableIds as $position => $id) {
                Mediable::query()->whereKey($id)->update(['position' => $position]);
            }
            $this->touch($style, 'catalog.product.images_reordered', ['variant_id' => $variant->id]);
        });
    }

    /**
     * Áp bộ ảnh của `$source` (thay toàn bộ) cho mọi SKU khác của sản phẩm cùng giá trị ở trục `$optionTypeId`.
     *
     * @return int số SKU được áp
     */
    public function copyToSiblings(Style $style, Variant $source, int $optionTypeId): int
    {
        $valueId = DB::table('variant_option_values')->where('variant_id', $source->id)->where('option_type_id', $optionTypeId)->value('option_value_id');
        if ($valueId === null) {
            throw ValidationException::withMessages(['option_type_id' => __('catalog::messages.option_value_invalid')]);
        }
        $siblings = Variant::query()->where('style_id', $style->id)->whereKeyNot($source->id)
            ->whereIn('id', DB::table('variant_option_values')->where('option_type_id', $optionTypeId)->where('option_value_id', $valueId)->select('variant_id'))
            ->get();
        $images = $source->gallery()->get(['media_id', 'position', 'alt']);

        DB::transaction(function () use ($style, $source, $siblings, $images): void {
            foreach ($siblings as $sibling) {
                $sibling->gallery()->delete();
                foreach ($images as $image) {
                    $sibling->gallery()->create(['media_id' => $image->media_id, 'role' => 'gallery', 'position' => $image->position, 'alt' => $image->alt]);
                }
            }
            $this->touch($style, 'catalog.product.images_copied', ['variant_id' => $source->id, 'targets' => $siblings->count()]);
        });

        return $siblings->count();
    }

    private function guardCount(Variant $variant, int $adding): void
    {
        if ($variant->gallery()->count() + $adding > self::MAX_IMAGES) {
            throw ValidationException::withMessages(['images' => __('catalog::messages.too_many_images', ['max' => self::MAX_IMAGES])]);
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function touch(Style $style, string $action, array $changes): void
    {
        $style->touch();
        $this->audit->record($action, 'style', $style->id, $changes);
        event(new ProductUpdated($style->id, $style->style_code));
    }
}
