<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Extension point (tag `vani.inventory.stock_links`, 0.3.88): nút trên trang Admin → Tồn kho do plugin đóng góp (vd.
 * `vani.warehouse`: Chuyển kho, Kho & cửa hàng). Chỉ plugin đang bật mới có nút — tắt plugin là nút biến mất.
 * `managesLocations()` = plugin quản lý danh sách kho → Core ẩn form "Kho chính" (cửa hàng một kho).
 */
interface StockPageLink
{
    public const TAG = 'vani.inventory.stock_links';

    public function label(): string;

    /**
     * URL; null = ẩn (vd. nhân viên không có quyền).
     */
    public function url(): ?string;

    public function managesLocations(): bool;
}
