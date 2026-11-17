<?php

declare(strict_types=1);

namespace Modules\Returns\Domain;

/**
 * Bất biến số lượng/tiền của đổi trả (docs/09-order/order.md §7): mỗi dòng trả tối đa số đã giao trừ số đang/đã trả;
 * tiền hoàn khi hoàn tất trong [0, số tính được] (CSKH được trừ phí hư hỏng, không được hoàn hơn).
 */
final class ReturnLimits
{
    /**
     * @param  array<int, int>  $delivered  dòng đơn => số đã giao
     * @param  array<int, int>  $taken  dòng đơn => số đang/đã trả (yêu cầu tính vào giới hạn)
     * @return array<int, int> dòng đơn => số còn trả được (≥ 0)
     */
    public static function remaining(array $delivered, array $taken): array
    {
        $remaining = [];
        foreach ($delivered as $lineId => $quantity) {
            $remaining[$lineId] = max(0, $quantity - ($taken[$lineId] ?? 0));
        }

        return $remaining;
    }

    /**
     * Dòng đầu tiên vượt giới hạn: [dòng, số còn trả được] — null nếu mọi dòng hợp lệ (dòng không giao → còn 0).
     *
     * @param  array<int, int>  $requested
     * @param  array<int, int>  $delivered
     * @param  array<int, int>  $taken
     * @return array{0: int, 1: int}|null
     */
    public static function firstExceeded(array $requested, array $delivered, array $taken): ?array
    {
        foreach ($requested as $lineId => $quantity) {
            $allowed = max(0, ($delivered[$lineId] ?? 0) - ($taken[$lineId] ?? 0));
            if ($quantity > $allowed) {
                return [(int) $lineId, $allowed];
            }
        }

        return null;
    }

    public static function refundWithinLimit(int $amount, int $computed): bool
    {
        return $amount >= 0 && $amount <= $computed;
    }
}
