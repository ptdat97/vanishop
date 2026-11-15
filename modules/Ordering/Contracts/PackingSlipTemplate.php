<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

/**
 * Extension point (tag `vani.ordering.packing_slips`, EP v3 X3): mẫu phiếu giao của shop ngoài mẫu Core (A5) — khổ A6
 * cho máy in nhiệt, logo, QR Zalo OA, lời cảm ơn, ghi chú "cho xem hàng"… Nhân viên chọn mẫu ở thanh công cụ trang in
 * (`?template=<code>`).
 *
 * View (Blade, namespace của plugin) nhận cùng dữ liệu với mẫu Core: `$shopName`, `$orders` (mỗi phần tử: `number`,
 * `barcode` (SVG), `placed_at`, `recipient`, `phone`, `address`, `shipping_method`, `lines` [`name`, `sku`, `variant`,
 * `quantity`], `item_count`, `collect_on_delivery`, `total`, `note`), `$templates` + `$current` (bộ chọn mẫu, đặt trong
 * phần tử có class `toolbar` để không in ra).
 */
interface PackingSlipTemplate
{
    public const TAG = 'vani.ordering.packing_slips';

    public function code(): string;

    public function label(): string;

    /** Tên view Blade, vd. `vani-hello::slip-a6`. */
    public function view(): string;
}
