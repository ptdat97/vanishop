<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\Contracts\Data\IncomingStockData;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract hàng đang về (ADR-047): nhân viên, plugin hoặc ERP ghi số lượng + ngày dự kiến theo SKU. Core không cộng
 * hàng đang về vào số bán được — plugin đọc để hiển thị "Hàng đang về" hoặc tính hạn mức đặt trước.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface IncomingStock
{
    /**
     * Ghi số đang về của SKU (thay số cũ). `quantity` ≤ 0 → xoá. `source`: `staff` | `system:<id plugin>` | mã ERP.
     */
    public function set(int $variantId, int $quantity, ?string $expectedOn, string $source): void;

    /**
     * @param  list<int>  $variantIds
     * @return array<int, IncomingStockData> variant id => hàng đang về (không có: bỏ qua)
     */
    public function forVariants(array $variantIds): array;

    /**
     * Hàng đang về có ngày dự kiến ≤ `$date` (Y-m-d) — 0.5.36, để nhắc CSKH đơn đặt trước quá ngày dự kiến (L37).
     *
     * @return array<int, IncomingStockData> variant id => hàng đang về
     */
    public function dueBy(string $date): array;
}
