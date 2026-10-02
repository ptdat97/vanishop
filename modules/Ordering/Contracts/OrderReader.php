<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use DateTimeInterface;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderLineData;

/**
 * Service contract: đọc đơn (snapshot).
 */
interface OrderReader
{
    public function find(int $orderId): ?OrderData;

    public function findByPublicId(string $publicId): ?OrderData;

    public function findByNumber(string $number): ?OrderData;

    /**
     * @return list<OrderLineData>
     */
    public function lines(int $orderId): array;

    /**
     * Khách đã từng đặt đơn (không tính đơn đã huỷ) chưa — dùng cho rule "đơn đầu tiên".
     */
    public function customerHasPlacedOrder(int $customerId): bool;

    /**
     * Đơn thay đổi sau một mốc, theo thứ tự (updated_at, id) tăng dần — cho đồng bộ kéo (keyset pagination).
     * Trang sau: truyền updated_at + id của đơn cuối trang trước.
     *
     * @return list<OrderData>
     */
    /**
     * Thống kê đơn (không tính đơn đã huỷ) của một khách.
     *
     * @return array{orders_count: int, total_spent: int, first_order_at: ?string, last_order_at: ?string}
     */
    public function customerStats(int $customerId): array;

    public function changedSince(?DateTimeInterface $since, ?int $afterId, int $limit): array;
}
