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
        return 'Lợi nhuận gộp theo sản phẩm';
    }

    public function description(): string
    {
        return 'Doanh thu − giá vốn theo SKU (đơn không huỷ, chưa gồm phí giao). SKU thiếu giá vốn được ghi rõ, không tính vào lợi nhuận.';
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
                    new Column('sku', 'SKU'), new Column('product', 'Sản phẩm'), new Column('quantity', 'Số lượng', 'number'), new Column('revenue', 'Doanh thu', 'money'),
                    new Column('cost', 'Giá vốn', 'money'), new Column('profit', 'Lợi nhuận gộp', 'money'), new Column('margin', 'Biên LN', 'percent'), new Column('note', 'Ghi chú'),
                ],
                array_map(fn (array $row): array => [
                    'sku' => $row['sku'], 'product' => $row['product'], 'quantity' => $row['quantity'], 'revenue' => $row['revenue'],
                    'cost' => $row['missing'] ? null : $row['cost'],
                    'profit' => $row['missing'] ? null : $row['revenue'] - $row['cost'],
                    'margin' => $row['missing'] || $row['revenue'] === 0 ? null : round(($row['revenue'] - $row['cost']) * 100 / $row['revenue'], 1),
                    'note' => $row['missing'] ? 'Thiếu giá vốn' : '',
                ], array_slice(array_values($variants), 0, self::LIMIT)),
            ),
            [
                new Metric('Lợi nhuận gộp', $total['profit'], 'money', $total['margin'] === null ? null : "Biên {$total['margin']}%"),
                new Metric('Doanh thu có giá vốn', $total['revenue'], 'money'),
                new Metric('Giá vốn', $total['cost'], 'money'),
                new Metric('SKU thiếu giá vốn', $missing, 'number', $this->missingHint($missing, $total['uncosted_revenue'], count($variants))),
            ],
        );
    }

    private function missingHint(int $missing, int $uncostedRevenue, int $variants): ?string
    {
        if (! $this->grossProfit->hasCostSource()) {
            return 'Chưa có nguồn giá vốn — bật plugin Giá vốn và nhập giá vốn theo SKU';
        }
        $hint = $missing === 0 ? null : 'Doanh thu '.number_format($uncostedRevenue, 0, ',', '.').' ₫ chưa tính lợi nhuận';

        return $variants > self::LIMIT ? trim(($hint ?? '').' · Bảng hiện '.self::LIMIT.' SKU đầu', ' ·') : $hint;
    }
}
