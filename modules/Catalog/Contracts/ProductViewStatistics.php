<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract (0.3.79, §3.5-2): lượt xem sản phẩm đã gom theo giờ — cho báo cáo, khối "xem nhiều", Admin.
 */
interface ProductViewStatistics
{
    /**
     * Ghi một lượt xem: cùng `$viewer` (định danh phiên ẩn danh, không lưu xuống DB) + cùng mẫu trong 30 phút chỉ tính
     * một; mẫu không hiển thị bị bỏ. Phát ProductViewed khi được tính.
     */
    public function record(int $productId, string $viewer, ?int $customerId): bool;

    /**
     * @param  list<int>  $productIds
     * @return array<int, int> product id => lượt xem trong `$days` ngày gần nhất (không có lượt nào thì không có mặt)
     */
    public function counts(array $productIds, int $days = 30): array;

    /**
     * @return array<int, int> product id => lượt xem, nhiều trước
     */
    public function top(int $days = 30, int $limit = 10): array;
}
