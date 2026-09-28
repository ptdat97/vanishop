<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Service contract: nhập lại hàng về kho (hoàn về từ hãng vận chuyển, đổi/trả). Ghi movement `return`.
 * Location do hệ thống ngoài quản lý tồn (ERP) chỉ ghi sổ, không đổi on_hand (ERP đồng bộ số thật).
 */
interface InventoryReturns
{
    public function restock(int $locationId, int $variantId, int $quantity, string $reason, string $reference): void;
}
