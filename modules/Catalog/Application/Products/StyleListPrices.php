<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\ListPrices;

/**
 * Ghi `styles.list_price_min` (bản sao giá niêm yết thấp nhất cho tìm kiếm). Không tăng `lock_version`: đây không phải
 * thao tác sửa sản phẩm của nhân viên, không được làm form sản phẩm đang mở báo "bản ghi đã đổi".
 */
final class StyleListPrices implements ListPrices
{
    public function setMinimums(array $minimumByStyle): void
    {
        if ($minimumByStyle === []) {
            return;
        }

        $current = DB::table('styles')->whereIn('id', array_keys($minimumByStyle))->pluck('list_price_min', 'id');
        foreach ($minimumByStyle as $styleId => $minimum) {
            if (! $current->has($styleId)) {
                continue;
            }
            $before = $current->get($styleId);
            if (($before === null ? null : (int) $before) !== $minimum) {
                DB::table('styles')->where('id', $styleId)->update(['list_price_min' => $minimum]);
            }
        }

    }
}
