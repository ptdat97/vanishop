<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Seo;

use Modules\Catalog\Contracts\MediaUrls;
use Modules\Storefront\View\PageMeta;
use Modules\Tenancy\Contracts\Settings;

/**
 * Giá trị mặc định khi chia sẻ link và cho Google (Admin → Cấu hình): mô tả cửa hàng (`core`/`storefront.meta_description`
 * — mô tả trang chủ, và mô tả dự phòng cho trang không có mô tả riêng) và ảnh chia sẻ (`core`/`storefront.share_image` —
 * ảnh Facebook/Zalo hiện khi trang không có ảnh riêng; logo trong dữ liệu cấu trúc của cửa hàng).
 */
final class StoreShare
{
    public const DESCRIPTION_SETTING = 'storefront.meta_description';

    public const IMAGE_SETTING = 'storefront.share_image';

    /** Bản lớn cho thẻ chia sẻ (Facebook khuyên ≥ 1200 px bề ngang) — một trong `vanishop.media.cache.widths`. */
    private const IMAGE_WIDTH = 1600;

    public function __construct(
        private readonly Settings $settings,
        private readonly MediaUrls $media,
    ) {}

    /**
     * @return array{site_name: string, description: string|null, image: string|null}
     */
    public function defaults(): array
    {
        $description = trim((string) $this->settings->get('core', self::DESCRIPTION_SETTING, ''));
        $imageId = (int) $this->settings->get('core', self::IMAGE_SETTING, 0);

        return [
            'site_name' => (string) config('app.name'),
            'description' => $description === '' ? null : $description,
            'image' => $imageId > 0 ? PageMeta::absoluteUrl($this->media->url($imageId, self::IMAGE_WIDTH)) : null,
        ];
    }
}
