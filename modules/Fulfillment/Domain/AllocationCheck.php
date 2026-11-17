<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Domain;

/**
 * Phương án chia vận đơn của SourcingStrategy phải khớp hàng đang giữ (docs/09-order/fulfillment.md §3): mỗi dòng đơn đủ
 * đúng số lượng, mỗi (kho, SKU) không vượt số đang giữ ở kho đó, không dòng lạ/số lượng < 1. Không khớp → không tạo vận
 * đơn (tránh xuất kho hàng không giữ).
 */
final class AllocationCheck
{
    /**
     * @param  list<array{location: int, lines: array<int, int>}>  $proposals  kho => (dòng đơn => số lượng)
     * @param  array<int, array{variant: int, quantity: int}>  $orderLines  dòng đơn => SKU + số lượng
     * @param  list<array{location: int, variant: int, quantity: int}>  $reserved
     */
    public static function matches(array $proposals, array $orderLines, array $reserved): bool
    {
        $byLine = [];
        $byStock = [];
        foreach ($proposals as $proposal) {
            foreach ($proposal['lines'] as $lineId => $quantity) {
                if (! isset($orderLines[$lineId]) || $quantity < 1) {
                    return false;
                }
                $byLine[$lineId] = ($byLine[$lineId] ?? 0) + $quantity;
                $key = "{$proposal['location']}:{$orderLines[$lineId]['variant']}";
                $byStock[$key] = ($byStock[$key] ?? 0) + $quantity;
            }
        }

        $reservedByStock = [];
        foreach ($reserved as $row) {
            $key = "{$row['location']}:{$row['variant']}";
            $reservedByStock[$key] = ($reservedByStock[$key] ?? 0) + $row['quantity'];
        }

        foreach ($orderLines as $lineId => $line) {
            if (($byLine[$lineId] ?? 0) !== $line['quantity']) {
                return false;
            }
        }
        foreach ($byStock as $key => $quantity) {
            if ($quantity > ($reservedByStock[$key] ?? 0)) {
                return false;
            }
        }

        return true;
    }
}
