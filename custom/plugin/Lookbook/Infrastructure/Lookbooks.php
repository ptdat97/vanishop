<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Infrastructure;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreLocale;
use Plugin\Lookbook\Persistence\Lookbook;
use Plugin\Lookbook\Persistence\LookbookImage;

/**
 * Lưu/xoá lookbook (đặc tả §3.2, §8): slug trống → sinh từ tên ngôn ngữ mặc định; ảnh từ Thư viện ảnh (khai báo đang
 * dùng để thư viện không cho xoá); xoá lookbook thì bỏ khai báo.
 */
final class Lookbooks
{
    public const OWNER = 'plg.lookbook';

    public const MAX_IMAGES = 60;

    public function __construct(private readonly MediaDirectory $media) {}

    /**
     * @param  array{slug?: ?string, translations: array<string, array<string, ?string>>, cover_media_id?: ?int, category_id?: ?int, position?: ?int, active?: bool, images?: list<array{media_id: int, alt?: array<string, ?string>}>}  $data
     */
    public function save(?Lookbook $lookbook, array $data): Lookbook
    {
        $default = StoreLocale::default();
        $translations = [];
        foreach (StoreLocale::supported() as $locale) {
            $values = array_map(fn (?string $value): ?string => $value === null || trim($value) === '' ? null : trim($value), (array) ($data['translations'][$locale] ?? []));
            $translations[$locale] = array_intersect_key($values, array_flip(['name', 'description', 'meta_title', 'meta_description']));
        }
        if (($translations[$default]['name'] ?? null) === null) {
            throw ValidationException::withMessages(["translations.{$default}.name" => 'Cần tên ở ngôn ngữ mặc định.']);
        }

        $slug = Str::slug((string) (($data['slug'] ?? '') ?: $translations[$default]['name']));
        if ($slug === '' || Lookbook::query()->where('slug', $slug)->when($lookbook !== null, fn ($query) => $query->whereKeyNot($lookbook->id))->exists()) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn trống hoặc đã có lookbook khác dùng.']);
        }

        $images = array_values(array_filter($data['images'] ?? [], fn (array $image): bool => $image['media_id'] > 0));
        if (count($images) > self::MAX_IMAGES) {
            throw ValidationException::withMessages(['images' => 'Tối đa '.self::MAX_IMAGES.' ảnh.']);
        }
        $cover = isset($data['cover_media_id']) ? (int) $data['cover_media_id'] : null;
        $mediaIds = array_values(array_unique([...array_map(fn (array $image): int => (int) $image['media_id'], $images), ...($cover === null ? [] : [$cover])]));
        $found = $mediaIds === [] ? [] : $this->media->find($mediaIds);
        if (count($found) !== count($mediaIds)) {
            throw ValidationException::withMessages(['images' => 'Có ảnh không còn trong Thư viện ảnh.']);
        }

        return DB::transaction(function () use ($lookbook, $data, $translations, $slug, $images, $cover): Lookbook {
            $lookbook ??= new Lookbook;
            $lookbook->fill([
                'slug' => $slug, 'translations' => $translations, 'cover_media_id' => $cover,
                'category_id' => isset($data['category_id']) && (int) $data['category_id'] > 0 ? (int) $data['category_id'] : null,
                'position' => (int) ($data['position'] ?? 0), 'active' => (bool) ($data['active'] ?? false),
                'lock_version' => ($lookbook->lock_version ?? 0) + 1,
            ])->save();

            LookbookImage::query()->where('lookbook_id', $lookbook->id)->delete();
            foreach ($images as $position => $image) {
                $alt = array_filter(array_map(fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, 200) : null, (array) ($image['alt'] ?? [])));
                LookbookImage::query()->create(['lookbook_id' => $lookbook->id, 'media_id' => (int) $image['media_id'], 'alt' => $alt === [] ? null : $alt, 'position' => $position]);
            }

            $this->media->syncUsages(self::OWNER, $lookbook->id, 'gallery', array_map(fn (array $image): int => (int) $image['media_id'], $images));
            $this->media->syncUsages(self::OWNER, $lookbook->id, 'cover', $cover === null ? [] : [$cover]);

            return $lookbook->refresh();
        });
    }

    public function delete(Lookbook $lookbook): void
    {
        DB::transaction(function () use ($lookbook): void {
            $lookbook->delete();
            $this->media->releaseUsages(self::OWNER, $lookbook->id);
        });
    }
}
