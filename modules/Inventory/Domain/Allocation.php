<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

/**
 * Phân bổ số lượng cần giữ của một variant vào các location theo thứ tự ưu tiên cho sẵn:
 * lấy tối đa ATS của location đầu, phần còn thiếu sang location kế tiếp. Thiếu tổng → InsufficientStock.
 */
final class Allocation
{
    /**
     * @param  list<StockLevel>  $levels  theo thứ tự ưu tiên
     * @return array<int, int> location id => số lượng
     */
    public static function allocate(int $variantId, int $quantity, array $levels): array
    {
        $total = array_sum(array_map(fn (StockLevel $level): int => $level->ats(), $levels));
        if ($quantity > $total) {
            throw new InsufficientStock($variantId, $quantity, $total);
        }

        $plan = [];
        $remaining = $quantity;
        foreach ($levels as $level) {
            if ($remaining === 0) {
                break;
            }
            $take = min($remaining, $level->ats());
            if ($take > 0) {
                $plan[$level->locationId] = $take;
                $remaining -= $take;
            }
        }

        return $plan;
    }
}
