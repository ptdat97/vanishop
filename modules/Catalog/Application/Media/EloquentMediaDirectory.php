<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\MediaData;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\Mediable;

/**
 * Nơi dùng ảnh của plugin ghi vào `mediables` như ảnh của Core, nên Thư viện ảnh đếm và chặn xoá giống nhau.
 */
final class EloquentMediaDirectory implements MediaDirectory
{
    public function __construct(private readonly ImageCache $cache) {}

    public function find(array $ids): array
    {
        $result = [];
        foreach (Media::query()->whereKey(array_values(array_unique($ids)))->get() as $media) {
            $variants = [];
            foreach ($this->cache->widthsFor($media) as $width) {
                $variants[$width] = $media->url($width);
            }
            $result[$media->id] = new MediaData($media->id, $media->original_name, $media->width, $media->height, $media->url(), $variants);
        }

        return $result;
    }

    public function syncUsages(string $ownerType, int $ownerId, string $role, array $mediaIds): void
    {
        $existing = Media::query()->whereKey($mediaIds)->pluck('id')->all();
        $ordered = array_values(array_unique(array_filter($mediaIds, fn (int $id): bool => in_array($id, $existing, true))));

        DB::transaction(function () use ($ownerType, $ownerId, $role, $ordered): void {
            Mediable::query()->where(['mediable_type' => $ownerType, 'mediable_id' => $ownerId, 'role' => $role])->delete();
            foreach ($ordered as $position => $mediaId) {
                Mediable::query()->create(['media_id' => $mediaId, 'mediable_type' => $ownerType, 'mediable_id' => $ownerId, 'role' => $role, 'position' => $position]);
            }
        });
    }

    public function releaseUsages(string $ownerType, ?int $ownerId = null): void
    {
        Mediable::query()->where('mediable_type', $ownerType)->when($ownerId !== null, fn ($query) => $query->where('mediable_id', $ownerId))->delete();
    }
}
