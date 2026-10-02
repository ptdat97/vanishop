<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Widgets;

use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class Revenue30DaysWidget implements DashboardWidget
{
    public function __construct(private readonly OrderStatistics $statistics) {}

    public function key(): string
    {
        return 'reports_revenue_30d';
    }

    public function label(): string
    {
        return 'Doanh thu 30 ngày';
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function width(): int
    {
        return 1;
    }

    public function order(): int
    {
        return 30;
    }

    public function render(): Metric
    {
        $period = ReportPeriod::fromPreset('30d');
        $totals = $this->statistics->totals($period->from, $period->to);

        return new Metric($this->label(), $totals->revenue, 'money', sprintf('%s đơn · TB %s ₫/đơn', number_format($totals->ordersCount, 0, ',', '.'), number_format($totals->averageOrderValue(), 0, ',', '.')));
    }
}
