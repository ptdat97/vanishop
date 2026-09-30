<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\Contracts\Data\SyncOutcome;

/**
 * Service contract: nhận tồn vật lý (số tuyệt đối + version) từ authority ngoài (ERP/POS/ODO) của một location.
 * Chỉ authority của location (`locations.stock_authority`) được ghi; bản cũ hơn `sync_version` bị bỏ qua.
 *
 * @see docs/08-inventory/inventory.md §7
 */
interface InventorySync
{
    /**
     * @param  list<string>  $locationCodes
     *
     * @throws NotStockAuthority location đầu tiên không tồn tại hoặc không do $authority quản lý tồn
     */
    public function assertAuthority(string $authority, array $locationCodes): void;

    /**
     * @throws NotStockAuthority location không tồn tại hoặc không do $authority quản lý tồn
     */
    public function syncOnHand(string $authority, string $locationCode, int $variantId, int $onHand, int $version, string $reason): SyncOutcome;
}
