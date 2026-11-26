<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure;

use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Ordering\Contracts\OrderStatistics;

/**
 * Tốc độ bán theo SKU trong kỳ báo cáo (reference-parity L41 — gợi ý nhập lại, hàng bán chậm): số lượng bán của đơn không
 * huỷ (sau huỷ một phần), chia đều cho số ngày của kỳ.
 */
final class StockVelocity
{
    public function __construct(private readonly OrderStatistics $orders, private readonly VariantDirectory $variants) {}

    /**
     * @return array<int, int> variant id => số lượng bán trong kỳ
     */
    public function sold(ReportPeriod $period): array
    {
        $sold = [];
        foreach ($this->orders->variantSales($period->from, $period->to, $period->timezone) as $line) {
            $sold[$line['variant_id']] = ($sold[$line['variant_id']] ?? 0) + $line['quantity'];
        }

        return $sold;
    }

    public static function days(ReportPeriod $period): int
    {
        return max(1, (int) round(($period->to->getTimestamp() - $period->from->getTimestamp()) / 86400));
    }

    /**
     * SKU và tên sản phẩm (kèm tuỳ chọn) cho các dòng sẽ hiển thị.
     *
     * @param  list<int>  $variantIds
     * @return array<int, array{sku: string, product: string}>
     */
    public function labels(array $variantIds): array
    {
        return array_map(fn (VariantData $variant): array => [
            'sku' => $variant->sku,
            'product' => trim($variant->productName.' '.implode(' / ', array_filter(array_map(fn (array $option): string => (string) ($option['value'] ?? ''), $variant->options)))),
        ], $variantIds === [] ? [] : $this->variants->find($variantIds));
    }
}
