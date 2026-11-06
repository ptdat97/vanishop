<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Infrastructure;

use Modules\Catalog\Contracts\Data\ImageSize;
use Modules\Catalog\Contracts\ImageSizeProvider;

/**
 * Kích thước ảnh riêng của lookbook (tham chiếu cho `ImageSizeProvider`, Core 0.3.87): ảnh bìa ở lưới lookbook cắt
 * khung dọc 2:3 cho đều nhau. Nhân viên chỉnh được ở Admin → Thư viện ảnh → Kích thước ảnh.
 */
final class LookbookImageSizes implements ImageSizeProvider
{
    public const COVER = 'lookbook.cover';

    public function sizes(): array
    {
        return [new ImageSize(self::COVER, 'Ảnh bìa lookbook (lưới)', 600, 900, crop: true)];
    }
}
