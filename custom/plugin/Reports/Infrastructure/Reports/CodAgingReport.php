<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Fulfillment\Contracts\DeliveryStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class CodAgingReport implements ReportProvider
{
    public function __construct(private readonly DeliveryStatistics $deliveries) {}

    public function key(): string
    {
        return 'cod-aging';
    }

    public function label(): string
    {
        return __('vani-reports::texts.cod_aging.title');
    }

    public function description(): string
    {
        return __('vani-reports::texts.cod_aging.description');
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $rows = $this->deliveries->codAging($period->from, $period->to, now()->toDateTimeImmutable());

        return new ReportResult(new Table([
            new Column('carrier', __('vani-reports::texts.cod_aging.carrier')),
            new Column('bucket', __('vani-reports::texts.cod_aging.bucket')),
            new Column('shipments', __('vani-reports::texts.cod_aging.shipments'), 'number'),
            new Column('amount', __('vani-reports::texts.cod_aging.amount'), 'money'),
        ], $rows), [
            new Metric(__('vani-reports::texts.cod_aging.amount'), array_sum(array_column($rows, 'amount')), 'money'),
        ]);
    }
}
