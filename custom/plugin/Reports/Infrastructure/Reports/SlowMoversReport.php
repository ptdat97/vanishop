<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Inventory\Contracts\StockOverview;
use Plugin\Reports\Infrastructure\StockVelocity;
use Plugin\Reports\ReportsServiceProvider;

/**
 * Hàng bán chậm (reference-parity L41): SKU đang bán còn tồn, xếp theo số ngày đủ bán với tốc độ của kỳ đang xem (không bán
 * được gì trong kỳ xếp đầu, tồn nhiều trước). Duyệt tồn theo lô — giữ tối đa MAX_ROWS dòng chậm nhất.
 */
final class SlowMoversReport implements ReportProvider
{
    private const MAX_ROWS = 200;

    /** Đủ bán quá số ngày này (với tốc độ hiện tại) mới tính là chậm. */
    public const SLOW_DAYS = 90;

    public function __construct(private readonly StockVelocity $velocity, private readonly StockOverview $stock) {}

    public function key(): string
    {
        return 'slow-movers';
    }

    public function label(): string
    {
        return __('vani-reports::texts.slow_movers_report.label');
    }

    public function description(): string
    {
        return __('vani-reports::texts.slow_movers_report.description', ['days' => self::SLOW_DAYS]);
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $text = fn (string $key, array $replace = []): string => __("vani-reports::texts.slow_movers_report.{$key}", $replace);
        $sold = $this->velocity->sold($period);
        $days = StockVelocity::days($period);
        $slow = [];
        $count = $units = 0;
        $after = 0;
        while (($batch = $this->stock->stockedVariants($after)) !== []) {
            $after = (int) array_key_last($batch);
            foreach ($batch as $variantId => $sellable) {
                $perDay = ($sold[$variantId] ?? 0) / $days;
                $cover = $perDay > 0 ? $sellable / $perDay : null; // null = không bán được trong kỳ
                if ($cover !== null && $cover <= self::SLOW_DAYS) {
                    continue;
                }
                $count++;
                $units += $sellable;
                // Khoá sắp xếp: không bán trước (∞), rồi số ngày đủ bán giảm dần, rồi tồn giảm dần.
                $slow[] = ['variant_id' => $variantId, 'sellable' => $sellable, 'sold' => $sold[$variantId] ?? 0, 'cover' => $cover === null ? null : (int) round($cover)];
                if (count($slow) > self::MAX_ROWS * 2) {
                    $slow = $this->top($slow);
                }
            }
        }
        $slow = $this->top($slow);
        $labels = $this->velocity->labels(array_column($slow, 'variant_id'));

        return new ReportResult(
            new Table(
                [
                    new Column('sku', $text('sku')), new Column('product', $text('product')), new Column('sellable', $text('sellable'), 'number'),
                    new Column('sold', $text('sold'), 'number'), new Column('cover', $text('cover_days')),
                ],
                array_map(fn (array $row): array => ['sku' => $labels[$row['variant_id']]['sku'] ?? (string) $row['variant_id'], 'product' => $labels[$row['variant_id']]['product'] ?? '',
                    'sellable' => $row['sellable'], 'sold' => $row['sold'], 'cover' => $row['cover'] === null ? $text('no_sales') : (string) $row['cover']], $slow),
            ),
            [
                new Metric($text('slow_skus'), $count, 'number', $count > self::MAX_ROWS ? $text('showing_top', ['count' => self::MAX_ROWS]) : null),
                new Metric($text('slow_units'), $units, 'number'),
            ],
        );
    }

    /**
     * @param  list<array{variant_id: int, sellable: int, sold: int, cover: int|null}>  $rows
     * @return list<array{variant_id: int, sellable: int, sold: int, cover: int|null}>
     */
    private function top(array $rows): array
    {
        usort($rows, fn (array $a, array $b): int => [$b['cover'] === null, $b['cover'] ?? 0, $b['sellable'], $a['variant_id']]
            <=> [$a['cover'] === null, $a['cover'] ?? 0, $a['sellable'], $b['variant_id']]);

        return array_slice($rows, 0, self::MAX_ROWS);
    }
}
