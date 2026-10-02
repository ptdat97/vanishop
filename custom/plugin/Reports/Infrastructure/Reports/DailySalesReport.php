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
        return 'Doanh thu theo ngày';
    }

    public function description(): string
    {
        return 'Doanh thu, số đơn, giá trị trung bình và số khách theo từng ngày.';
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
                [new Column('date', 'Ngày'), new Column('orders', 'Số đơn', 'number'), new Column('revenue', 'Doanh thu', 'money'), new Column('average', 'TB/đơn', 'money')],
                array_map(fn (SalesBucket $day): array => [
                    'date' => $day->key, 'orders' => $day->ordersCount, 'revenue' => $day->revenue,
                    'average' => $day->ordersCount === 0 ? 0 : intdiv($day->revenue, $day->ordersCount),
                ], $days),
            ),
            [
                new Metric('Doanh thu', $totals->revenue, 'money', "Giảm giá {$this->vnd($totals->discount)} · Phí ship {$this->vnd($totals->shipping)}"),
                new Metric('Số đơn', $totals->ordersCount, 'number', "Đã huỷ {$totals->cancelledCount}"),
                new Metric('Giá trị TB/đơn', $totals->averageOrderValue(), 'money'),
                new Metric('Khách mua', $totals->customersCount, 'number'),
            ],
            new Series(
                array_map(fn (SalesBucket $day): string => $day->label, $days),
                array_map(fn (SalesBucket $day): int => $day->revenue, $days),
                'money',
                'Doanh thu theo ngày',
            ),
        );
    }

    private function vnd(int $amount): string
    {
        return number_format($amount, 0, ',', '.').' ₫';
    }
}
