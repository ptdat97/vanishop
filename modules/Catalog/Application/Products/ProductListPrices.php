<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\ListingAvailability;
use Modules\Catalog\Contracts\ListPrices;

/**
 * Ghi giá niêm yết thấp nhất (Pricing) và "còn hàng" (Inventory) vào read model `product_listing` — bản sao chỉ để lọc và
 * sắp xếp trang danh sách. Không tăng `lock_version`: không phải thao tác sửa sản phẩm, không được làm form sản phẩm đang
 * mở báo "bản ghi đã đổi". Sản phẩm chưa có dòng read model (chưa dựng) thì bỏ qua — ProductListingProjector giữ nguyên hai
 * cột này khi dựng lại.
 */
final class ProductListPrices implements ListingAvailability, ListPrices
{
    public function setMinimums(array $minimumByProduct): void
    {
        $this->write('list_price_min', $minimumByProduct, fn (?int $value): ?int => $value);
    }

    public function setInStock(array $inStockByProduct): void
    {
        $this->write('in_stock', $inStockByProduct, fn (bool $value): bool => $value);
    }

    /**
     * @param  array<int, mixed>  $byProduct
     */
    private function write(string $column, array $byProduct, \Closure $cast): void
    {
        if ($byProduct === []) {
            return;
        }
        $current = DB::table('product_listing')->whereIn('product_id', array_keys($byProduct))->pluck($column, 'product_id');
        foreach ($byProduct as $productId => $value) {
            if (! $current->has($productId)) {
                continue;
            }
            $before = $current->get($productId);
            $value = $cast($value);
            if (($before === null ? null : (is_bool($value) ? (bool) $before : (int) $before)) !== $value) {
                DB::table('product_listing')->where('product_id', $productId)->update([$column => $value]);
            }
        }
    }
}
