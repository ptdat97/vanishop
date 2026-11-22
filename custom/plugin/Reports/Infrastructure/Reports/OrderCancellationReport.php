<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class OrderCancellationReport implements ReportProvider
{
    public function __construct(private readonly OrderStatistics $orders) {}

    public function key(): string
    {
        return 'order-cancellations';
    }

    public function label(): string
    {
        return __('vani-reports::texts.order_cancellations.title');
    }

    public function description(): string
    {
        return __('vani-reports::texts.order_cancellations.description');
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $rows = $this->orders->cancellations($period->from, $period->to);

        return new ReportResult(new Table([
            new Column('code', __('vani-reports::texts.order_cancellations.code')),
            new Column('label', __('vani-reports::texts.order_cancellations.reason')),
            new Column('count', __('vani-reports::texts.order_cancellations.count'), 'number'),
        ], $rows), [
            new Metric(__('vani-reports::texts.order_cancellations.count'), array_sum(array_column($rows, 'count')), 'number'),
        ]);
    }
}
