<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Modules\Catalog\Persistence\Models\Media;

/**
 * Model ảnh đã đọc trong request (scoped), dùng chung cho `MediaUrls` và `MediaDirectory`: cùng một ảnh hiện ở nhiều khối
 * (Builder, CMS, thẻ sản phẩm) chỉ tra một lần. Lưu/xoá ảnh thì `forget()` (CatalogServiceProvider).
 */
final class MediaModels
{
    /** @var array<int, Media|null> null = đã tra, không có */
    private array $loaded = [];

    /**
     * @param  list<int>  $ids
     * @return array<int, Media> theo id, bỏ id không có
     */
    public function get(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $missing = array_values(array_diff($ids, array_keys($this->loaded)));
        if ($missing !== []) {
            $this->loaded += array_fill_keys($missing, null);
            foreach (Media::query()->whereKey($missing)->get() as $media) {
                $this->loaded[$media->id] = $media;
            }
        }

        return array_filter(array_intersect_key($this->loaded, array_flip($ids)));
    }

    public function forget(): void
    {
        $this->loaded = [];
    }
}
