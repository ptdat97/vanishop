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
        return __('vani-reports::texts.gross_profit_daily_report.gross_profit_day');
    }

    public function description(): string
    {
        return __('vani-reports::texts.gross_profit_daily_report.revenue_cost_gross_profit_per');
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
                    new Column('date', __('vani-reports::texts.gross_profit_daily_report.date')), new Column('revenue', __('vani-reports::texts.gross_profit_daily_report.revenue_cost_price'), 'money'), new Column('cost', __('vani-reports::texts.gross_profit_daily_report.cost_price'), 'money'),
                    new Column('profit', __('vani-reports::texts.gross_profit_daily_report.gross_profit'), 'money'), new Column('margin', __('vani-reports::texts.gross_profit_daily_report.margin'), 'percent'), new Column('uncosted', __('vani-reports::texts.gross_profit_daily_report.revenue_without_cost_price'), 'money'),
                ],
                $rows,
            ),
            [
                new Metric(__('vani-reports::texts.gross_profit_daily_report.gross_profit'), $total['profit'], 'money', $total['margin'] === null ? null : __('vani-reports::texts.gross_profit_daily_report.margin_margin', ['margin' => $total['margin']])),
                new Metric(__('vani-reports::texts.gross_profit_daily_report.cost_price'), $total['cost'], 'money'),
                new Metric(__('vani-reports::texts.gross_profit_daily_report.revenue_without_cost_price'), $total['uncosted_revenue'], 'money', $this->grossProfit->hasCostSource() ? null : __('vani-reports::texts.gross_profit_daily_report.no_cost_price_source_enable')),
            ],
            new Series(
                array_map(fn (array $row): string => (new DateTimeImmutable($row['date']))->format('d/m'), $rows),
                array_map(fn (array $row): int => $row['profit'], $rows),
                'money',
                __('vani-reports::texts.gross_profit_daily_report.gross_profit_day'),
            ),
        );
    }
}
