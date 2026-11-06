<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract (0.3.69, ADR-037 — `vani_image()`): URL ảnh của Thư viện ảnh theo id, chọn bản thu nhỏ rộng ≥ $width
 * (không có thì ảnh gốc). Lưu id, tra URL lúc hiển thị — URL bản thu nhỏ đổi khi đổi định dạng/disk/kích thước.
 *
 * 0.3.87: `$size` nhận thêm **tên** kích thước (plugin khai báo qua `ImageSizeProvider`, vd. `lookbook.cover`); `$group`
 * `product` = cắt theo tỉ lệ ảnh sản phẩm (Admin → Thư viện ảnh → Kích thước ảnh, mặc định 2:3).
 */
interface MediaUrls
{
    /**
     * null khi id không tồn tại.
     */
    public function url(int $mediaId, int|string|null $size = null, ?string $group = null): ?string;

    /**
     * Batch cho danh sách (một truy vấn).
     *
     * @param  list<int>  $mediaIds
     * @return array<int, string> id => URL (id không tồn tại thì không có mặt)
     */
    public function urls(array $mediaIds, int|string|null $size = null, ?string $group = null): array;

    /**
     * Ảnh theo id; id null hoặc không tồn tại → ảnh thay thế cấu hình ở `core`/`storefront.placeholder_image`;
     * null khi chưa cấu hình ảnh thay thế (theme tự vẽ khung trống).
     */
    public function image(?int $mediaId, int|string|null $size = null, ?string $group = null): ?string;

    /**
     * Giá trị `srcset` (0.3.87) cho thẻ `<img>`; rỗng khi id không tồn tại hoặc ảnh không có bản thu nhỏ.
     */
    public function srcset(?int $mediaId, ?string $group = null): string;

    /**
     * Tỉ lệ khung ảnh sản phẩm [rộng, cao] (0.3.87, mặc định [2, 3]) cho theme dựng khung không nhảy; null = giữ tỉ lệ gốc.
     *
     * @return array{int, int}|null
     */
    public function productRatio(): ?array;
}
