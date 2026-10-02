<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Widgets;

use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Ordering\Contracts\OrderStatistics;

final class OrdersToProcessWidget implements DashboardWidget
{
    public function __construct(private readonly OrderStatistics $statistics) {}

    public function key(): string
    {
        return 'reports_orders_to_process';
    }

    public function label(): string
    {
        return 'Đơn cần xử lý';
    }

    public function permission(): string
    {
        return 'orders.view';
    }

    public function width(): int
    {
        return 1;
    }

    public function order(): int
    {
        return 20;
    }

    public function render(): Metric
    {
        $counts = $this->statistics->countByStatus();
        $pending = $counts['pending'] ?? 0;
        $confirmed = $counts['confirmed'] ?? 0;
        $processing = $counts['processing'] ?? 0;

        return new Metric($this->label(), $pending + $confirmed + $processing, 'number', "Chờ xác nhận {$pending} · Đã xác nhận {$confirmed} · Đang xử lý {$processing}");
    }
}
