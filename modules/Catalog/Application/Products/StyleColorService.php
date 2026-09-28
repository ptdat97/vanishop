<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Màu của style và bộ ảnh theo màu.
 */
final class StyleColorService
{
    public const MAX_IMAGES_PER_COLOR = 20;

    public function __construct(
        private readonly MediaLibrary $media,
        private readonly AuditLogger $audit,
    ) {}

    public function addColor(Style $style, int $colorId): StyleColor
    {
        $color = Color::query()->where('brand_id', $style->brand_id)->find($colorId);
        if ($color === null) {
            throw ValidationException::withMessages(['color_id' => __('catalog::messages.color_not_in_brand')]);
        }
        if ($style->colors()->where('color_id', $colorId)->exists()) {
            throw ValidationException::withMessages(['color_id' => __('catalog::messages.color_already_added')]);
        }

        return DB::transaction(function () use ($style, $color): StyleColor {
            $styleColor = $style->colors()->create(['color_id' => $color->id, 'position' => (int) $style->colors()->max('position') + 1]);
            $this->touch($style, 'catalog.product.color_added', ['color' => $color->code]);

            return $styleColor;
        });
    }

    public function removeColor(Style $style, StyleColor $styleColor): void
    {
        if ($styleColor->variants()->exists()) {
            throw ValidationException::withMessages(['color_id' => __('catalog::messages.color_has_variants')]);
        }

        DB::transaction(function () use ($style, $styleColor): void {
            $styleColor->gallery()->delete();
            $styleColor->delete();
            $this->touch($style, 'catalog.product.color_removed', ['style_color_id' => $styleColor->id]);
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function addImages(Style $style, StyleColor $styleColor, array $files): void
    {
        if ($styleColor->gallery()->count() + count($files) > self::MAX_IMAGES_PER_COLOR) {
            throw ValidationException::withMessages(['images' => __('catalog::messages.too_many_images', ['max' => self::MAX_IMAGES_PER_COLOR])]);
        }

        DB::transaction(function () use ($style, $styleColor, $files): void {
            $position = (int) $styleColor->gallery()->max('position');
            foreach ($files as $file) {
                $media = $this->media->store($style->brand_id, $file);
                $styleColor->gallery()->create(['media_id' => $media->id, 'role' => 'gallery', 'position' => ++$position]);
            }
            $this->touch($style, 'catalog.product.images_added', ['style_color_id' => $styleColor->id, 'count' => count($files)]);
        });
    }

    public function removeImage(Style $style, StyleColor $styleColor, Mediable $image): void
    {
        $image->delete();
        $this->touch($style, 'catalog.product.image_removed', ['style_color_id' => $styleColor->id]);
    }

    /**
     * @param  list<int>  $mediableIds  thứ tự mới
     */
    public function reorderImages(Style $style, StyleColor $styleColor, array $mediableIds): void
    {
        DB::transaction(function () use ($style, $styleColor, $mediableIds): void {
            $current = $styleColor->gallery()->pluck('id')->all();
            if (array_diff($mediableIds, $current) !== [] || count($mediableIds) !== count($current)) {
                throw ValidationException::withMessages(['order' => __('catalog::messages.image_order_invalid')]);
            }
            foreach ($mediableIds as $position => $id) {
                Mediable::query()->whereKey($id)->update(['position' => $position]);
            }
            $this->touch($style, 'catalog.product.images_reordered', ['style_color_id' => $styleColor->id]);
        });
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function touch(Style $style, string $action, array $changes): void
    {
        $style->touch();
        $this->audit->record($action, 'style', $style->id, $changes);
        event(new ProductUpdated($style->id, $style->brand_id, $style->style_code));
    }
}
