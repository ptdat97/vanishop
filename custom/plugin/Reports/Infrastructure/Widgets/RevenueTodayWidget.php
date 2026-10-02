<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Widgets;

use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class RevenueTodayWidget implements DashboardWidget
{
    public function __construct(private readonly OrderStatistics $statistics) {}

    public function key(): string
    {
        return 'reports_revenue_today';
    }

    public function label(): string
    {
        return 'Doanh thu hôm nay';
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
        return 10;
    }

    public function render(): Metric
    {
        $period = ReportPeriod::fromPreset('today');
        $totals = $this->statistics->totals($period->from, $period->to);

        return new Metric($this->label(), $totals->revenue, 'money', "{$totals->ordersCount} đơn");
    }
}
