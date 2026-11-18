<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use DateTimeImmutable;
use DateTimeZone;
use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Series;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Plugin\Reports\Infrastructure\GrossProfit;
use Plugin\Reports\ReportsServiceProvider;

final class GrossProfitDailyReport implements ReportProvider
{
    public function __construct(private readonly GrossProfit $grossProfit) {}

    public function key(): string
    {
        return 'gross-profit-by-day';
    }

    public function label(): string
    {
        return 'Lợi nhuận gộp theo ngày';
    }

    public function description(): string
    {
        return 'Doanh thu, giá vốn, lợi nhuận gộp từng ngày (chưa gồm phí giao). Doanh thu của SKU thiếu giá vốn tách riêng.';
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $zone = new DateTimeZone($period->timezone);
        $byDay = [];
        for ($day = $period->from->setTimezone($zone)->setTime(0, 0); $day < $period->to; $day = $day->modify('+1 day')) {
            $byDay[$day->format('Y-m-d')] = [];
        }
        foreach ($this->grossProfit->lines($period) as $line) {
            $byDay[$line['day']][] = $line;
        }
        $rows = [];
        foreach ($byDay as $day => $lines) {
            $sum = GrossProfit::sum($lines);
            $rows[] = ['date' => $day, 'revenue' => $sum['revenue'], 'cost' => $sum['cost'], 'profit' => $sum['profit'], 'margin' => $sum['margin'], 'uncosted' => $sum['uncosted_revenue']];
        }
        $total = GrossProfit::sum(array_merge(...array_values($byDay)));

        return new ReportResult(
            new Table(
                [
                    new Column('date', 'Ngày'), new Column('revenue', 'Doanh thu có giá vốn', 'money'), new Column('cost', 'Giá vốn', 'money'),
                    new Column('profit', 'Lợi nhuận gộp', 'money'), new Column('margin', 'Biên LN', 'percent'), new Column('uncosted', 'Doanh thu thiếu giá vốn', 'money'),
                ],
                $rows,
            ),
            [
                new Metric('Lợi nhuận gộp', $total['profit'], 'money', $total['margin'] === null ? null : "Biên {$total['margin']}%"),
                new Metric('Giá vốn', $total['cost'], 'money'),
                new Metric('Doanh thu thiếu giá vốn', $total['uncosted_revenue'], 'money', $this->grossProfit->hasCostSource() ? null : 'Chưa có nguồn giá vốn — bật plugin Giá vốn'),
            ],
            new Series(
                array_map(fn (array $row): string => (new DateTimeImmutable($row['date']))->format('d/m'), $rows),
                array_map(fn (array $row): int => $row['profit'], $rows),
                'money',
                'Lợi nhuận gộp theo ngày',
            ),
        );
    }
}
