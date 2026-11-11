<?php

declare(strict_types=1);

use Modules\Catalog\Contracts\MediaUrls;

/*
| Helper ảnh/media của Catalog cho plugin và theme (ADR-037, danh sách đóng). Tách khỏi modules/Extension/helpers.php:
| vòng 0 không biết thương mại (R29) nên helper này nằm ở module sở hữu contract. Class trong modules/ KHÔNG dùng
| (inject contract) — arch test chặn.
*/

if (! function_exists('vani_image')) {
    /**
     * URL ảnh của Thư viện ảnh: `$size` số = bản thu nhỏ rộng ≥ số đó, chuỗi = kích thước có tên (plugin, vd.
     * `lookbook.cover`, 0.3.87); `$group` `product` = cắt theo tỉ lệ ảnh sản phẩm. Id null/không có → ảnh thay thế của
     * cửa hàng; chưa cấu hình ảnh thay thế → chuỗi rỗng (theme tự vẽ khung trống).
     */
    function vani_image(?int $mediaId, int|string $size, ?string $group = null): string
    {
        return app(MediaUrls::class)->image($mediaId, $size, $group) ?? '';
    }
}

if (! function_exists('vani_product_ratio')) {
    /**
     * Tỉ lệ khung ảnh sản phẩm [rộng, cao] cho theme (0.3.87, Admin → Thư viện ảnh → Kích thước ảnh; mặc định [2, 3]).
     * "Giữ tỉ lệ gốc" → [3, 4] làm khung mặc định của theme.
     *
     * @return array{int, int}
     */
    function vani_product_ratio(): array
    {
        return app(MediaUrls::class)->productRatio() ?? [3, 4];
    }
}

if (! function_exists('vani_media')) {
    /**
     * URL file gốc của ảnh/video trong Thư viện ảnh (0.3.91 — video không có bản thu nhỏ). Chuỗi rỗng khi id không có.
     */
    function vani_media(?int $mediaId): string
    {
        return $mediaId === null ? '' : (app(MediaUrls::class)->url($mediaId) ?? '');
    }
}

if (! function_exists('vani_srcset')) {
    /**
     * Giá trị `srcset` của ảnh (0.3.87): `<img src="{{ vani_image($id, 800) }}" srcset="{{ vani_srcset($id) }}" sizes="…">`.
     * Chuỗi rỗng khi không có ảnh/bản thu nhỏ.
     */
    function vani_srcset(?int $mediaId, ?string $group = null): string
    {
        return app(MediaUrls::class)->srcset($mediaId, $group);
    }
}
