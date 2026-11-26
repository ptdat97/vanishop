<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract (0.5.90, db-refactor §4.3): Inventory ghi "còn hàng" của sản phẩm sang read model trang danh sách
 * (`product_listing.in_stock`) — Catalog không đọc tồn kho. Còn hàng = ít nhất một SKU đang bán có tồn bán được (sản phẩm
 * không theo dõi tồn luôn còn hàng).
 */
interface ListingAvailability
{
    /**
     * Idempotent; sản phẩm chưa có trong read model thì bỏ qua.
     *
     * @param  array<int, bool>  $inStockByProduct  product id => còn hàng
     */
    public function setInStock(array $inStockByProduct): void;
}
