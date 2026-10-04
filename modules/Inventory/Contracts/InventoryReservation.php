<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\Contracts\Data\ReservationRequest;
use Modules\Inventory\Contracts\Data\ReservedLine;

/**
 * Service contract: giữ hàng (invariant của Core). Gọi TRONG transaction đặt hàng của bên gọi.
 * Không đủ hàng → Modules\Inventory\Contracts\StockUnavailable.
 */
interface InventoryReservation
{
    /**
     * Idempotent theo key: key đã có reservation active → trả lại các dòng đã giữ.
     *
     * @return list<ReservedLine>
     */
    public function reserve(ReservationRequest $request): array;

    /**
     * Bỏ giữ hàng (huỷ đơn, hết hạn thanh toán). Idempotent.
     */
    public function release(string $key, string $reason): void;

    /**
     * Xuất kho: chuyển reservation sang committed. Idempotent.
     */
    public function commit(string $key): void;

    /**
     * Nhả MỘT PHẦN hàng giữ còn active của `$key` (huỷ một phần đơn, 0.3.18): theo variant, nhả từ dòng giữ mới nhất.
     * Số nhả vượt số đang giữ → nhả hết phần đang giữ của variant đó. Idempotent theo gọi lại: không.
     *
     * @param  array<int, int>  $quantities  variant_id => số lượng
     */
    public function releaseQuantities(string $key, array $quantities, string $reason): void;

    /**
     * Các dòng đang giữ (active) của key: location nào giữ bao nhiêu — Fulfillment dùng để tạo shipment theo kho.
     *
     * @return list<ReservedLine>
     */
    public function reservedLines(string $key): array;
}
