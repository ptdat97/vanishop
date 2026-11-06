<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Service contract (0.3.88, ADR-042): biến động tồn vật lý do vận hành kho ghi (chuyển kho của `vani.warehouse`). Khoá
 * dòng tồn theo thứ tự cố định, ghi sổ biến động append-only (`transfer_out`/`transfer_in`), phát `AvailabilityChanged`.
 *
 * Gọi bên trong `DB::transaction` của nơi gọi (cùng kết nối) để đổi trạng thái chứng từ và tồn là một transaction.
 * Chỉ kho do VaniShop quản lý tồn vật lý; kho của hệ thống ngoài → StockMovementRejected.
 */
interface StockMovements
{
    /**
     * Xuất khỏi kho: trừ `on_hand`. Không đủ hàng (tồn − đang giữ) → StockMovementRejected, không ghi gì.
     *
     * @param  array<int, int>  $quantities  variant_id => số lượng (> 0)
     */
    public function transferOut(int $locationId, array $quantities, string $reason, string $reference): void;

    /**
     * Nhập vào kho: cộng `on_hand`.
     *
     * @param  array<int, int>  $quantities  variant_id => số lượng (> 0)
     */
    public function transferIn(int $locationId, array $quantities, string $reason, string $reference): void;
}
