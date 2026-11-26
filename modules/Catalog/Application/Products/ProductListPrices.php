<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\ListPrices;

/**
 * Ghi `products.list_price_min` (bản sao giá niêm yết thấp nhất cho tìm kiếm). Không tăng `lock_version`: đây không phải
 * thao tác sửa sản phẩm của nhân viên, không được làm form sản phẩm đang mở báo "bản ghi đã đổi".
 */
final class ProductListPrices implements ListPrices
{
    public function setMinimums(array $minimumByProduct): void
    {
        if ($minimumByProduct === []) {
            return;
        }

        $current = DB::table('products')->whereIn('id', array_keys($minimumByProduct))->pluck('list_price_min', 'id');
        foreach ($minimumByProduct as $productId => $minimum) {
            if (! $current->has($productId)) {
                continue;
            }
            $before = $current->get($productId);
            if (($before === null ? null : (int) $before) !== $minimum) {
                DB::table('products')->where('id', $productId)->update(['list_price_min' => $minimum]);
            }
        }

    }
}
