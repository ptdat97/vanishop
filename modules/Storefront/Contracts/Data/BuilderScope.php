<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;
use Modules\Storefront\Contracts\ScopedBuilderSurface;

/**
 * Mô tả một phạm vi của {@see ScopedBuilderSurface} cho trình biên tập Admin.
 *
 * - `label`: tên hiển thị, vd. "Thương hiệu Lumière".
 * - `previewUrl`: trang storefront của đối tượng (nút "Xem trang"); null = không có (đối tượng đang ẩn).
 * - `backUrl`: trang Admin của đối tượng (nút quay lại).
 * - `listingType` + `listingSlug`: ngữ cảnh trang danh sách cho bản xem trước nháp (block theo ngữ cảnh như lưới sản phẩm).
 */
#[PluginApi]
final readonly class BuilderScope
{
    public function __construct(
        public string $label,
        public ?string $previewUrl = null,
        public ?string $backUrl = null,
        public ?string $listingType = null,
        public ?string $listingSlug = null,
    ) {}
}
