<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Plugin\Reports\Infrastructure\GrossProfit;
use Plugin\Reports\ReportsServiceProvider;

final class GrossProfitByProductReport implements ReportProvider
{
    private const LIMIT = 500;

    public function __construct(private readonly GrossProfit $grossProfit) {}

    public function key(): string
    {
        return 'gross-profit-by-product';
    }

    public function label(): string
    {
        return __('vani-reports::texts.gross_profit_by_product_report.gross_profit_product');
    }

    public function description(): string
    {
        return __('vani-reports::texts.gross_profit_by_product_report.revenue_cost_sku_non_cancelled');
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $variants = [];
        foreach ($this->grossProfit->lines($period) as $line) {
            $row = $variants[$line['variant_id']] ?? ['sku' => $line['sku'], 'product' => $line['product_name'], 'quantity' => 0, 'revenue' => 0, 'cost' => 0, 'missing' => false];
            $row['quantity'] += $line['quantity'];
            $row['revenue'] += $line['revenue'];
            $row['missing'] = $row['missing'] || $line['cost'] === null;
            $row['cost'] += (int) $line['cost'];
            $variants[$line['variant_id']] = $row;
        }
        uasort($variants, fn (array $a, array $b): int => [$b['revenue'], $a['sku']] <=> [$a['revenue'], $b['sku']]);
        $lines = array_map(fn (array $row): array => ['revenue' => $row['revenue'], 'cost' => $row['missing'] ? null : $row['cost']], $variants);
        $total = GrossProfit::sum(array_values($lines));
        $missing = count(array_filter($variants, fn (array $row): bool => $row['missing']));

        return new ReportResult(
            new Table(
                [
                    new Column('sku', 'SKU'), new Column('product', __('vani-reports::texts.gross_profit_by_product_report.products')), new Column('quantity', __('vani-reports::texts.gross_profit_by_product_report.quantity'), 'number'), new Column('revenue', 'Doanh thu', 'money'),
                    new Column('cost', __('vani-reports::texts.gross_profit_by_product_report.cost_price'), 'money'), new Column('profit', __('vani-reports::texts.gross_profit_by_product_report.gross_profit'), 'money'), new Column('margin', __('vani-reports::texts.gross_profit_by_product_report.margin'), 'percent'), new Column('note', __('vani-reports::texts.gross_profit_by_product_report.note')),
                ],
                array_map(fn (array $row): array => [
                    'sku' => $row['sku'], 'product' => $row['product'], 'quantity' => $row['quantity'], 'revenue' => $row['revenue'],
                    'cost' => $row['missing'] ? null : $row['cost'],
                    'profit' => $row['missing'] ? null : $row['revenue'] - $row['cost'],
                    'margin' => $row['missing'] || $row['revenue'] === 0 ? null : round(($row['revenue'] - $row['cost']) * 100 / $row['revenue'], 1),
                    'note' => $row['missing'] ? __('vani-reports::texts.gross_profit_by_product_report.missing_cost_price') : '',
                ], array_slice(array_values($variants), 0, self::LIMIT)),
            ),
            [
                new Metric(__('vani-reports::texts.gross_profit_by_product_report.gross_profit'), $total['profit'], 'money', $total['margin'] === null ? null : __('vani-reports::texts.gross_profit_by_product_report.margin_margin', ['margin' => $total['margin']])),
                new Metric(__('vani-reports::texts.gross_profit_by_product_report.revenue_cost_price'), $total['revenue'], 'money'),
                new Metric(__('vani-reports::texts.gross_profit_by_product_report.cost_price'), $total['cost'], 'money'),
                new Metric(__('vani-reports::texts.gross_profit_by_product_report.skus_without_cost_price'), $missing, 'number', $this->missingHint($missing, $total['uncosted_revenue'], count($variants))),
            ],
        );
    }

    private function missingHint(int $missing, int $uncostedRevenue, int $variants): ?string
    {
        if (! $this->grossProfit->hasCostSource()) {
            return __('vani-reports::texts.gross_profit_by_product_report.no_cost_price_source_enable');
        }
        $hint = $missing === 0 ? null : 'Doanh thu '.number_format($uncostedRevenue, 0, ',', '.').__('vani-reports::texts.gross_profit_by_product_report.not_included_profit');

        return $variants > self::LIMIT ? trim(($hint ?? '').__('vani-reports::texts.gross_profit_by_product_report.table_shows_first').self::LIMIT.__('vani-reports::texts.gross_profit_by_product_report.skus'), ' ·') : $hint;
    }
}
