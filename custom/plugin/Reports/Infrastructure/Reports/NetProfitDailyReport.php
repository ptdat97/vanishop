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
use Plugin\Reports\Infrastructure\NetProfit;
use Plugin\Reports\ReportsServiceProvider;

/**
 * Lợi nhuận sau trả hàng và phí giao theo ngày đặt (reference-parity L43).
 */
final class NetProfitDailyReport implements ReportProvider
{
    public function __construct(private readonly NetProfit $netProfit, private readonly GrossProfit $grossProfit) {}

    public function key(): string
    {
        return 'net-profit-by-day';
    }

    public function label(): string
    {
        return __('vani-reports::texts.net_profit_daily_report.label');
    }

    public function description(): string
    {
        return __('vani-reports::texts.net_profit_daily_report.description');
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $text = fn (string $key, array $replace = []): string => __("vani-reports::texts.net_profit_daily_report.{$key}", $replace);
        $data = $this->netProfit->byDay($period);
        $zone = new DateTimeZone($period->timezone);
        $total = ['revenue' => 0, 'cost' => 0, 'quantity' => 0, 'uncosted_revenue' => 0, 'refunds' => 0, 'uncosted_refunds' => 0, 'returned_cost' => 0, 'returned_quantity' => 0, 'carrier_fees' => 0, 'missing_fees' => 0, 'return_shipping' => 0];
        $zero = $total;
        $rows = [];
        for ($day = $period->from->setTimezone($zone)->setTime(0, 0); $day < $period->to; $day = $day->modify('+1 day')) {
            $values = $data[$day->format('Y-m-d')] ?? $zero;
            foreach ($values as $key => $value) {
                $total[$key] += $value;
            }
            $result = NetProfit::result($values);
            $rows[] = [
                'date' => $day->format('Y-m-d'), 'revenue' => $values['revenue'], 'cost' => $values['cost'], 'refunds' => $values['refunds'],
                'restocked_cost' => $values['returned_cost'], 'carrier_fees' => $values['carrier_fees'], 'return_shipping' => $values['return_shipping'],
                'profit' => $result['profit'], 'return_rate' => $result['return_rate'],
            ];
        }
        $overall = NetProfit::result($total);

        return new ReportResult(
            new Table(
                [
                    new Column('date', $text('date')), new Column('revenue', $text('revenue'), 'money'), new Column('cost', $text('cost_price'), 'money'),
                    new Column('refunds', $text('refunds'), 'money'), new Column('restocked_cost', $text('restocked_cost'), 'money'),
                    new Column('carrier_fees', $text('carrier_fees'), 'money'), new Column('return_shipping', $text('return_shipping'), 'money'),
                    new Column('profit', $text('net_profit'), 'money'), new Column('return_rate', $text('return_rate'), 'percent'),
                ],
                $rows,
            ),
            [
                new Metric($text('net_profit'), $overall['profit'], 'money', $total['missing_fees'] > 0 ? $text('missing_fees_hint', ['count' => $total['missing_fees']]) : null),
                new Metric($text('return_rate'), $overall['return_rate'] ?? 0, 'percent'),
                new Metric($text('carrier_fees'), $total['carrier_fees'], 'money'),
                new Metric($text('uncosted_refunds'), $total['uncosted_refunds'], 'money', $this->grossProfit->hasCostSource() ? null : __('vani-reports::texts.gross_profit_daily_report.no_cost_price_source_enable')),
            ],
            new Series(
                array_map(fn (array $row): string => (new DateTimeImmutable($row['date']))->format('d/m'), $rows),
                array_map(fn (array $row): int => $row['profit'], $rows),
                'money',
                $text('net_profit_day'),
            ),
        );
    }
}
