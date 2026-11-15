<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

/**
 * Phân bổ số lượng cần giữ của một variant vào các location theo thứ tự ưu tiên cho sẵn:
 * lấy tối đa ATS của location đầu, phần còn thiếu sang location kế tiếp. Còn thiếu → hạn mức đặt trước của location
 * (ADR-047: hàng thật trước, suất đặt trước sau). Thiếu tổng → InsufficientStock.
 */
final class Allocation
{
    /**
     * @param  list<StockLevel>  $levels  theo thứ tự ưu tiên
     * @param  array<int, int>  $backorders  location id => hạn mức đặt trước
     * @return array<int, int> location id => số lượng
     */
    public static function allocate(int $variantId, int $quantity, array $levels, array $backorders = []): array
    {
        $total = array_sum(array_map(fn (StockLevel $level): int => $level->bookable($backorders[$level->locationId] ?? 0), $levels));
        if ($quantity > $total) {
            throw new InsufficientStock($variantId, $quantity, $total);
        }

        $plan = [];
        $remaining = $quantity;
        foreach ($levels as $level) {
            $take = min($remaining, $level->ats());
            if ($take > 0) {
                $plan[$level->locationId] = $take;
                $remaining -= $take;
            }
        }
        foreach ($levels as $level) {
            if ($remaining === 0) {
                break;
            }
            $extra = $level->bookable($backorders[$level->locationId] ?? 0) - $level->ats();
            $take = min($remaining, $extra);
            if ($take > 0) {
                $plan[$level->locationId] = ($plan[$level->locationId] ?? 0) + $take;
                $remaining -= $take;
            }
        }

        return $plan;
    }
}
