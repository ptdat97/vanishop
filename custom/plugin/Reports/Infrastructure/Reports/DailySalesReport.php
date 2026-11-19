<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Series;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Ordering\Contracts\Data\SalesBucket;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class DailySalesReport implements ReportProvider
{
    public function __construct(private readonly OrderStatistics $statistics) {}

    public function key(): string
    {
        return 'sales-by-day';
    }

    public function label(): string
    {
        return __('vani-reports::texts.daily_sales_report.revenue_day');
    }

    public function description(): string
    {
        return __('vani-reports::texts.daily_sales_report.revenue_orders_average_order_value');
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $totals = $this->statistics->totals($period->from, $period->to);
        $days = $this->statistics->daily($period->from, $period->to, $period->timezone);

        return new ReportResult(
            new Table(
                [new Column('date', __('vani-reports::texts.daily_sales_report.date')), new Column('orders', __('vani-reports::texts.daily_sales_report.orders'), 'number'), new Column('revenue', 'Doanh thu', 'money'), new Column('average', __('vani-reports::texts.daily_sales_report.avg_order'), 'money')],
                array_map(fn (SalesBucket $day): array => [
                    'date' => $day->key, 'orders' => $day->ordersCount, 'revenue' => $day->revenue,
                    'average' => $day->ordersCount === 0 ? 0 : intdiv($day->revenue, $day->ordersCount),
                ], $days),
            ),
            [
                new Metric('Doanh thu', $totals->revenue, 'money', __('vani-reports::texts.daily_sales_report.discounts_discount_shipping_shipping', ['discount' => $this->vnd($totals->discount), 'shipping' => $this->vnd($totals->shipping)])),
                new Metric(__('vani-reports::texts.daily_sales_report.orders'), $totals->ordersCount, 'number', __('vani-reports::texts.daily_sales_report.cancelled_cancelled_count', ['cancelled_count' => $totals->cancelledCount])),
                new Metric(__('vani-reports::texts.daily_sales_report.average_order_value'), $totals->averageOrderValue(), 'money'),
                new Metric(__('vani-reports::texts.daily_sales_report.buyers'), $totals->customersCount, 'number'),
            ],
            new Series(
                array_map(fn (SalesBucket $day): string => $day->label, $days),
                array_map(fn (SalesBucket $day): int => $day->revenue, $days),
                'money',
                __('vani-reports::texts.daily_sales_report.revenue_day'),
            ),
        );
    }

    private function vnd(int $amount): string
    {
        return number_format($amount, 0, ',', '.').' ₫';
    }
}
