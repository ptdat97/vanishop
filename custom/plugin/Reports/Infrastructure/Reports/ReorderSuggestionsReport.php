<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\IncomingStock;
use Plugin\Reports\Infrastructure\StockVelocity;
use Plugin\Reports\ReportsServiceProvider;

/**
 * Gợi ý nhập lại (reference-parity L41): SKU có bán trong kỳ mà tồn bán được + hàng đang về không đủ bán thêm một kỳ bằng
 * kỳ đang xem theo tốc độ bán hiện tại. Bỏ qua sản phẩm không theo dõi tồn.
 */
final class ReorderSuggestionsReport implements ReportProvider
{
    private const MAX_ROWS = 200;

    public function __construct(
        private readonly StockVelocity $velocity,
        private readonly AvailabilityReader $availability,
        private readonly IncomingStock $incoming,
    ) {}

    public function key(): string
    {
        return 'reorder-suggestions';
    }

    public function label(): string
    {
        return __('vani-reports::texts.reorder_suggestions_report.label');
    }

    public function description(): string
    {
        return __('vani-reports::texts.reorder_suggestions_report.description');
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $text = fn (string $key): string => __("vani-reports::texts.reorder_suggestions_report.{$key}");
        $sold = $this->velocity->sold($period);
        $days = StockVelocity::days($period);
        $rows = [];
        foreach (array_chunk(array_keys($sold), 500) as $chunk) {
            $ats = $this->availability->forVariants($chunk);
            $incoming = $this->incoming->forVariants($chunk);
            foreach ($chunk as $variantId) {
                $sellable = (int) ($ats[$variantId] ?? 0);
                if ($sellable >= AvailabilityReader::UNLIMITED) {
                    continue; // không theo dõi tồn
                }
                $coming = (int) ($incoming[$variantId]->quantity ?? 0);
                // Đủ bán thêm một kỳ bằng kỳ đang xem với tốc độ hiện tại = đúng số đã bán trong kỳ.
                $suggested = $sold[$variantId] - $sellable - $coming;
                if ($suggested > 0) {
                    $rows[] = ['variant_id' => $variantId, 'sold' => $sold[$variantId], 'per_day' => round($sold[$variantId] / $days, 1), 'sellable' => $sellable, 'incoming' => $coming, 'suggested' => $suggested];
                }
            }
        }
        usort($rows, fn (array $a, array $b): int => [$b['suggested'], $a['variant_id']] <=> [$a['suggested'], $b['variant_id']]);
        $total = count($rows);
        $units = array_sum(array_column($rows, 'suggested'));
        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $labels = $this->velocity->labels(array_column($rows, 'variant_id'));

        return new ReportResult(
            new Table(
                [
                    new Column('sku', $text('sku')), new Column('product', $text('product')), new Column('sold', $text('sold'), 'number'),
                    new Column('per_day', $text('per_day'), 'number'), new Column('sellable', $text('sellable'), 'number'),
                    new Column('incoming', $text('incoming'), 'number'), new Column('suggested', $text('suggested'), 'number'),
                ],
                array_map(fn (array $row): array => ['sku' => $labels[$row['variant_id']]['sku'] ?? (string) $row['variant_id'], 'product' => $labels[$row['variant_id']]['product'] ?? '',
                    'sold' => $row['sold'], 'per_day' => $row['per_day'], 'sellable' => $row['sellable'], 'incoming' => $row['incoming'], 'suggested' => $row['suggested']], $rows),
            ),
            [
                new Metric($text('skus_to_reorder'), $total, 'number', $total > self::MAX_ROWS ? __('vani-reports::texts.reorder_suggestions_report.showing_top', ['count' => self::MAX_ROWS]) : null),
                new Metric($text('units_to_reorder'), $units, 'number'),
            ],
        );
    }
}
