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
}
