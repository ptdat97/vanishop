<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Widgets;

use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\Series;
use Modules\Ordering\Contracts\Data\SalesBucket;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class Sales14DaysWidget implements DashboardWidget
{
    public function __construct(private readonly OrderStatistics $statistics) {}

    public function key(): string
    {
        return 'reports_sales_14d';
    }

    public function label(): string
    {
        return 'Doanh thu 14 ngày';
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function width(): int
    {
        return 2;
    }

    public function order(): int
    {
        return 40;
    }

    public function render(): Series
    {
        $today = ReportPeriod::fromPreset('today');
        $days = $this->statistics->daily($today->from->modify('-13 days'), $today->to, $today->timezone);

        return new Series(
            array_map(fn (SalesBucket $day): string => $day->label, $days),
            array_map(fn (SalesBucket $day): int => $day->revenue, $days),
            'money',
            $this->label(),
        );
    }
}
