<?php

declare(strict_types=1);

namespace Modules\Returns\Domain;

/**
 * Giá trị đổi hàng (docs/09-order/order.md §7.1): cùng mẫu (đổi size/màu) giữ đúng đơn giá đã mua, không tính chênh; khác
 * mẫu tính theo giá hiện tại, bù trừ với số khách đã trả cho các món trả khác mẫu — gộp lại rồi chia vào dòng thay thế
 * theo tỷ lệ thành tiền (dư làm tròn rải lần lượt). Khách bù phần thiếu (`payable`) hoặc được hoàn phần thừa (`refund`).
 */
final class ExchangeValuation
{
    /**
     * @param  array<int, array{quantity: int, unit_amount: int, same_style: bool, paid: int}>  $lines  dòng trả => giá trị
     *                                                                                                  (`unit_amount`: đơn giá đã mua nếu cùng mẫu, giá hiện tại nếu khác mẫu; `paid`: tiền khách đã trả cho món trả)
     * @return array{credits: array<int, int>, payable: int, refund: int}
     */
    public static function quote(array $lines): array
    {
        $credits = [];
        $subtotals = [];
        $paid = 0;
        foreach ($lines as $lineId => $line) {
            if ($line['same_style']) {
                $credits[$lineId] = $line['unit_amount'] * $line['quantity'];
            } else {
                $credits[$lineId] = 0;
                $subtotals[$lineId] = $line['unit_amount'] * $line['quantity'];
                $paid += $line['paid'];
            }
        }

        $value = array_sum($subtotals);
        $credit = min($paid, $value);
        $allocated = 0;
        foreach ($subtotals as $lineId => $subtotal) {
            $credits[$lineId] = $value === 0 ? 0 : intdiv($credit * $subtotal, $value);
            $allocated += $credits[$lineId];
        }
        foreach ($subtotals as $lineId => $subtotal) {
            $add = min($credit - $allocated, $subtotal - $credits[$lineId]);
            $credits[$lineId] += $add;
            $allocated += $add;
        }

        return ['credits' => $credits, 'payable' => $value - $credit, 'refund' => $paid - $credit];
    }
}
