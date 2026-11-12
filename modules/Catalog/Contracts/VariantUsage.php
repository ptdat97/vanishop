<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Extension point (variant-matrix §3.2–3.3): SKU nào đã phát sinh dữ liệu ở module/plugin của bạn (dòng đơn, dòng giỏ,
 * biến động tồn…). Tắt một ô trong ma trận biến thể: SKU có phát sinh ở bất kỳ nhà cung cấp nào → chỉ chuyển ngừng bán;
 * không phát sinh → mọi nhà cung cấp `release()` dữ liệu phụ thuộc không đáng giữ, rồi Catalog xoá hẳn SKU (cùng một
 * transaction). Nhà cung cấp lỗi khi kiểm tra được coi như "đã phát sinh" (an toàn: không xoá).
 */
interface VariantUsage
{
    public const TAG = 'vani.catalog.variant_usage';

    public function code(): string;

    /**
     * @param  list<int>  $variantIds
     * @return list<int> các SKU đã phát sinh dữ liệu
     */
    public function used(array $variantIds): array;

    /**
     * Dọn dữ liệu phụ thuộc không đáng giữ của các SKU sắp bị xoá (đã được mọi nhà cung cấp xác nhận chưa phát sinh) — vd.
     * giá trong bảng giá, dòng tồn bằng 0. Chạy trong transaction xoá của Catalog; lỗi → không xoá gì.
     *
     * @param  list<int>  $variantIds
     */
    public function release(array $variantIds): void;
}
