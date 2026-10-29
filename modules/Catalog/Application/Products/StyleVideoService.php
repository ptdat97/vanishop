<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Video;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Video của sản phẩm — phỏng theo Video của Vanilo (MIT, xem modules/Catalog/NOTICE). Một video (driver + reference)
 * dùng lại được cho nhiều sản phẩm; gỡ khỏi sản phẩm cuối cùng thì xoá video.
 */
final class StyleVideoService
{
    public const MAX_VIDEOS_PER_STYLE = 10;

    public function __construct(
        private readonly VideoDrivers $drivers,
        private readonly AuditLogger $audit,
    ) {}

    public function add(Style $style, string $driverCode, string $input, ?string $title = null): Video
    {
        $driver = $this->drivers->find($driverCode);
        if ($driver === null) {
            throw ValidationException::withMessages(['driver' => __('catalog::messages.video_driver_unavailable')]);
        }
        $reference = $driver->reference(trim($input));
        if ($reference === null) {
            throw ValidationException::withMessages(['url' => __('catalog::messages.video_reference_invalid', ['hint' => $driver->inputHint()])]);
        }
        if ($style->videos()->count() >= self::MAX_VIDEOS_PER_STYLE) {
            throw ValidationException::withMessages(['url' => __('catalog::messages.too_many_videos', ['max' => self::MAX_VIDEOS_PER_STYLE])]);
        }

        return DB::transaction(function () use ($style, $driver, $reference, $title): Video {
            $video = Video::query()->firstOrCreate(['driver' => $driver->code(), 'reference' => $reference]);
            if ($style->videos()->whereKey($video->id)->exists()) {
                throw ValidationException::withMessages(['url' => __('catalog::messages.video_already_added')]);
            }
            if (filled($title)) {
                $video->update(['title' => $title]);
            }
            $style->videos()->attach($video->id, ['position' => (int) DB::table('style_video')->where('style_id', $style->id)->max('position') + 1]);
            $this->audit->record('catalog.product.video_added', 'style', $style->id, ['driver' => $video->driver, 'reference' => $video->reference]);

            return $video;
        });
    }

    public function remove(Style $style, Video $video): void
    {
        DB::transaction(function () use ($style, $video): void {
            $style->videos()->detach($video->id);
            if ($video->styles()->doesntExist()) {
                $video->delete();
            }
            $this->audit->record('catalog.product.video_removed', 'style', $style->id, ['video_id' => $video->id]);
        });
    }

    /**
     * @param  list<int>  $videoIds  thứ tự mới
     */
    public function reorder(Style $style, array $videoIds): void
    {
        DB::transaction(function () use ($style, $videoIds): void {
            $current = DB::table('style_video')->where('style_id', $style->id)->pluck('video_id')->map(fn ($id): int => (int) $id)->all();
            if (array_diff($videoIds, $current) !== [] || count(array_unique($videoIds)) !== count($current)) {
                throw ValidationException::withMessages(['order' => __('catalog::messages.video_order_invalid')]);
            }
            foreach ($videoIds as $position => $videoId) {
                $style->videos()->updateExistingPivot($videoId, ['position' => $position]);
            }
            $this->audit->record('catalog.product.videos_reordered', 'style', $style->id, []);
        });
    }
}
