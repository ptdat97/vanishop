<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract (0.4.4): Pricing ghi giá niêm yết thấp nhất của sản phẩm sang Catalog để trang danh sách lọc và sắp
 * xếp theo giá (`ProductSearchQuery::$priceMin/$priceMax`, sort `price_asc`/`price_desc`). Catalog không đọc bảng giá
 * (không phụ thuộc Pricing) — đây là bản sao chỉ dùng cho tìm kiếm, không phải giá bán: giá hiển thị và giá tính tiền
 * luôn do `PriceResolver` quyết định.
 */
interface ListPrices
{
    /**
     * Ghi giá niêm yết thấp nhất theo sản phẩm. Idempotent; sản phẩm đổi giá được cập nhật vào chỉ mục tìm kiếm.
     *
     * @param  array<int, int|null>  $minimumByStyle  style id => số tiền (đơn vị nhỏ nhất), null = sản phẩm chưa có giá
     */
    public function setMinimums(array $minimumByStyle): void;
}
