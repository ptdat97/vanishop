<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\ImageSize;

/**
 * Extension point (tag `vani.catalog.image_sizes`, 0.3.87): plugin khai báo kích thước ảnh riêng (vd. ảnh bìa lookbook
 * 600×900 cắt khung). Core chỉ tạo bản thu nhỏ đúng các kích thước đã khai báo (cùng cấu hình Admin → Thư viện ảnh →
 * Kích thước ảnh, nơi nhân viên chỉnh được rộng/cao/cắt khung của từng kích thước). Dùng: `vani_image($mediaId, 'tên')`,
 * `MediaUrls::image($mediaId, 'tên')`. Tên trùng giữa plugin → plugin đăng ký trước thắng.
 */
interface ImageSizeProvider
{
    public const TAG = 'vani.catalog.image_sizes';

    /**
     * @return list<ImageSize>
     */
    public function sizes(): array;
}
