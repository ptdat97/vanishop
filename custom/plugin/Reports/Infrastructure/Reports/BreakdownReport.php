<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Ordering\Contracts\Data\SalesBucket;
use Modules\Ordering\Contracts\Data\SalesDimension;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\Reports\ReportsServiceProvider;

/**
 * Báo cáo doanh thu nhóm theo một chiều của `OrderStatistics::breakdown()`.
 */
abstract class BreakdownReport implements ReportProvider
{
    protected const LIMIT = 100;

    public function __construct(private readonly OrderStatistics $statistics) {}

    abstract protected function dimension(): SalesDimension;

    abstract protected function groupLabel(): string;

    /** Có cột số lượng (chiều theo dòng đơn). */
    protected function withQuantity(): bool
    {
        return false;
    }

    protected function displayLabel(SalesBucket $bucket): string
    {
        return $bucket->label !== '' ? $bucket->label : '(Không có)';
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $buckets = $this->statistics->breakdown($this->dimension(), $period->from, $period->to, static::LIMIT);
        $revenue = array_sum(array_map(fn (SalesBucket $bucket): int => $bucket->revenue, $buckets));

        $columns = [new Column('group', $this->groupLabel()), new Column('orders', 'Số đơn', 'number')];
        if ($this->withQuantity()) {
            $columns[] = new Column('quantity', 'Số lượng', 'number');
        }
        array_push($columns, new Column('revenue', 'Doanh thu', 'money'), new Column('share', 'Tỷ trọng', 'percent'));

        return new ReportResult(
            new Table($columns, array_map(fn (SalesBucket $bucket): array => [
                'group' => $this->displayLabel($bucket), 'orders' => $bucket->ordersCount, 'quantity' => $bucket->quantity,
                'revenue' => $bucket->revenue, 'share' => $revenue === 0 ? 0 : round($bucket->revenue * 100 / $revenue, 1),
            ], $buckets)),
            [new Metric('Doanh thu', $revenue, 'money'), new Metric($this->groupLabel(), count($buckets), 'number', count($buckets) >= static::LIMIT ? 'Hiển thị '.static::LIMIT.' nhóm đầu' : null)],
        );
    }
}
