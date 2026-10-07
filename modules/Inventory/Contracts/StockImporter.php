<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Service contract (0.3.27): đặt tồn tuyệt đối theo SKU tại một kho do VaniShop quản lý (`stock_authority = vanishop`) —
 * dữ liệu demo, kiểm kê nhập từ file. Đi qua sổ biến động (movement `sync` + audit), như kiểm kê trong Admin. Kho có
 * authority ngoài dùng InventorySync (Integration API), không dùng contract này.
 */
interface StockImporter
{
    /**
     * @param  array<string, int>  $quantities  sku => on_hand
     * @param  string|null  $locationCode  null = kho giao online ưu tiên cao nhất do VaniShop quản lý
     * @return array{location: string|null, updated: int, unknown: list<string>} location null = chưa có kho phù hợp (không ghi gì)
     */
    public function setOnHand(array $quantities, ?string $locationCode, string $reason): array;
}
