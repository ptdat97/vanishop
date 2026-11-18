<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Returns\Contracts\ReturnStatistics;
use Plugin\Reports\ReportsServiceProvider;

/**
 * Đổi/trả theo SKU (A10): số trả trong kỳ so với số bán trong kỳ — cùng khoảng thời gian (yêu cầu trả tạo trong kỳ, đơn đặt
 * trong kỳ), nên tỷ lệ là ước lượng khi hàng trả thuộc đơn kỳ trước.
 */
final class ReturnsByProductReport implements ReportProvider
{
    public function __construct(
        private readonly ReturnStatistics $returns,
        private readonly OrderStatistics $orders,
        private readonly VariantDirectory $variants,
    ) {}

    public function key(): string
    {
        return 'returns-by-product';
    }

    public function label(): string
    {
        return 'Đổi trả theo sản phẩm';
    }

    public function description(): string
    {
        return 'Số lượng trả, tỷ lệ trả (so với số bán trong cùng kỳ) và tiền hoàn theo SKU. Không tính yêu cầu bị từ chối/đã huỷ.';
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $returned = $this->returns->byVariant($period->from, $period->to);
        $sold = [];
        $names = [];
        foreach ($this->orders->variantSales($period->from, $period->to, $period->timezone) as $line) {
            $sold[$line['variant_id']] = ($sold[$line['variant_id']] ?? 0) + $line['quantity'];
            $names[$line['variant_id']] = [$line['sku'], $line['product_name']];
        }
        $unknown = array_values(array_diff(array_column($returned, 'variant_id'), array_keys($names)));
        foreach ($this->variants->find($unknown) as $id => $variant) {
            $names[$id] = [$variant->sku, $variant->styleName];
        }
        $soldTotal = array_sum($sold);
        $returnedTotal = array_sum(array_column($returned, 'quantity'));

        return new ReportResult(
            new Table(
                [
                    new Column('sku', 'SKU'), new Column('product', 'Sản phẩm'), new Column('sold', 'Đã bán', 'number'), new Column('returned', 'Trả lại', 'number'),
                    new Column('rate', 'Tỷ lệ trả', 'percent'), new Column('refund', 'Tiền hoàn', 'money'),
                ],
                array_map(fn (array $row): array => [
                    'sku' => $names[$row['variant_id']][0] ?? "#{$row['variant_id']}", 'product' => $names[$row['variant_id']][1] ?? '',
                    'sold' => $sold[$row['variant_id']] ?? 0, 'returned' => $row['quantity'],
                    'rate' => ($sold[$row['variant_id']] ?? 0) === 0 ? null : round($row['quantity'] * 100 / $sold[$row['variant_id']], 1),
                    'refund' => $row['refund_amount'],
                ], $returned),
            ),
            [
                new Metric('Số lượng trả', $returnedTotal, 'number', "Đã bán {$soldTotal}"),
                new Metric('Tỷ lệ trả', $soldTotal === 0 ? 0 : round($returnedTotal * 100 / $soldTotal, 1), 'percent'),
                new Metric('Tiền hoàn', array_sum(array_column($returned, 'refund_amount')), 'money'),
            ],
        );
    }
}
