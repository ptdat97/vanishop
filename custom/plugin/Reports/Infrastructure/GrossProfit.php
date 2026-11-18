<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure;

use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Facades\Hook;
use Modules\Ordering\Contracts\OrderStatistics;

/**
 * Lợi nhuận gộp (stabilization A10) = doanh thu dòng đơn − số lượng × giá vốn đơn vị. Giá vốn lấy qua hook
 * `vani.pricing.unit_costs` (vd. plugin `vani.cost-price`); SKU không có giá vốn không được tính vào giá vốn/lợi nhuận mà
 * báo riêng (doanh thu chưa tính lợi nhuận) — không coi giá vốn là 0.
 */
final class GrossProfit
{
    public function __construct(private readonly OrderStatistics $statistics) {}

    /**
     * Có nguồn giá vốn nào đang bật không.
     */
    public function hasCostSource(): bool
    {
        return Hook::hasListeners('vani.pricing.unit_costs');
    }

    /**
     * @return list<array{day: string, variant_id: int, sku: string, product_name: string, quantity: int, revenue: int, cost: int|null}>
     */
    public function lines(ReportPeriod $period): array
    {
        $sales = $this->statistics->variantSales($period->from, $period->to, $period->timezone);
        $costs = $this->unitCosts(array_values(array_unique(array_column($sales, 'variant_id'))));

        return array_map(fn (array $line): array => [
            ...$line,
            'cost' => isset($costs[$line['variant_id']]) ? $costs[$line['variant_id']] * $line['quantity'] : null,
        ], $sales);
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    private function unitCosts(array $variantIds): array
    {
        if ($variantIds === [] || ! $this->hasCostSource()) {
            return [];
        }
        $costs = [];
        foreach ((array) Hook::filter('vani.pricing.unit_costs', [], $variantIds) as $variantId => $amount) {
            if (in_array((int) $variantId, $variantIds, true) && is_numeric($amount)) {
                $costs[(int) $variantId] = (int) $amount;
            }
        }

        return $costs;
    }

    /**
     * Cộng dồn các dòng: doanh thu chỉ của SKU có giá vốn (`revenue`), doanh thu SKU thiếu giá vốn (`uncosted_revenue`).
     *
     * @param  list<array{revenue: int, cost: int|null}>  $lines
     * @return array{revenue: int, cost: int, profit: int, margin: float|null, uncosted_revenue: int}
     */
    public static function sum(array $lines): array
    {
        $revenue = $cost = $uncosted = 0;
        foreach ($lines as $line) {
            if ($line['cost'] === null) {
                $uncosted += $line['revenue'];
            } else {
                $revenue += $line['revenue'];
                $cost += $line['cost'];
            }
        }

        return ['revenue' => $revenue, 'cost' => $cost, 'profit' => $revenue - $cost, 'margin' => $revenue === 0 ? null : round(($revenue - $cost) * 100 / $revenue, 1), 'uncosted_revenue' => $uncosted];
    }
}
