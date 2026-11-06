<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Service contract (0.3.88, ADR-042): đối soát tồn với snapshot của một nguồn ngoài (ERP/POS — plugin `vani.warehouse`).
 * Ghi phiên + dòng chênh lệch vào bảng đối soát chung (Admin → Tồn kho → Đối soát); chỉ áp số lên tồn VaniShop khi kho
 * do chính nguồn đó quản lý (`stock_authority === source`), bản cũ hơn `sync_version` bị bỏ.
 */
interface InventoryReconciliation
{
    /**
     * @param  string  $source  mã authority ngoài (khác `vanishop`)
     * @param  list<array{location?: string, sku?: string, variant_id?: int, on_hand?: int, version?: int}>  $rows
     * @return array{id: int, checked: int, discrepancies: int, repaired: int, skipped: int}
     */
    public function reconcileExternal(string $source, array $rows, bool $apply = true): array;
}
