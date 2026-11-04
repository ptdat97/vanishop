<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract (0.3.69, ADR-037 — `vani_image()`): URL ảnh của Thư viện ảnh theo id, chọn bản thu nhỏ rộng ≥ $width
 * (không có thì ảnh gốc). Lưu id, tra URL lúc hiển thị — URL bản thu nhỏ đổi khi đổi định dạng/disk.
 */
interface MediaUrls
{
    /**
     * null khi id không tồn tại.
     */
    public function url(int $mediaId, ?int $width = null): ?string;

    /**
     * Batch cho danh sách (một truy vấn).
     *
     * @param  list<int>  $mediaIds
     * @return array<int, string> id => URL (id không tồn tại thì không có mặt)
     */
    public function urls(array $mediaIds, ?int $width = null): array;

    /**
     * Ảnh theo id; id null hoặc không tồn tại → ảnh thay thế cấu hình ở `core`/`storefront.placeholder_image`;
     * null khi chưa cấu hình ảnh thay thế (theme tự vẽ khung trống).
     */
    public function image(?int $mediaId, ?int $width = null): ?string;
}
