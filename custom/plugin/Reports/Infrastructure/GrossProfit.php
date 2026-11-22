<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure;

use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Facades\Hook;
use Modules\Ordering\Contracts\OrderStatistics;

/**
 * Lợi nhuận gộp từ các dòng đã chụp giá vốn lúc đặt. Doanh thu thiếu ảnh chụp được báo riêng.
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
     * @return list<array{day: string, variant_id: int, sku: string, product_name: string, quantity: int, revenue: int, costed_quantity: int, costed_revenue: int, cost_at_sale: int}>
     */
    public function lines(ReportPeriod $period): array
    {
        return $this->statistics->variantSales($period->from, $period->to, $period->timezone);
    }

    /**
     * Cộng dồn các dòng: doanh thu chỉ của SKU có giá vốn (`revenue`), doanh thu SKU thiếu giá vốn (`uncosted_revenue`).
     *
     * @param  list<array{revenue: int, costed_revenue: int, cost_at_sale: int}>  $lines
     * @return array{revenue: int, cost: int, profit: int, margin: float|null, uncosted_revenue: int}
     */
    public static function sum(array $lines): array
    {
        $revenue = $cost = $uncosted = 0;
        foreach ($lines as $line) {
            $uncosted += $line['revenue'] - $line['costed_revenue'];
            $revenue += $line['costed_revenue'];
            $cost += $line['cost_at_sale'];
        }

        return ['revenue' => $revenue, 'cost' => $cost, 'profit' => $revenue - $cost, 'margin' => $revenue === 0 ? null : round(($revenue - $cost) * 100 / $revenue, 1), 'uncosted_revenue' => $uncosted];
    }
}
