<?php

declare(strict_types=1);

namespace Plugin\SizeChart\Infrastructure;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreLocale;
use Plugin\SizeChart\Persistence\SizeChart;

/**
 * Bảng size: lưu (chuẩn hoá khối nội dung và luật gợi ý), gán cho sản phẩm/danh mục/thương hiệu, chọn bảng cho một sản
 * phẩm theo thứ tự ưu tiên và trình bày cho storefront. Đặc tả: docs/05-plugin/specs/size-chart.md.
 */
final class SizeCharts
{
    /** Đơn vị độ dài và giá trị quy về cm. */
    public const UNITS = ['cm' => 1.0, 'inch' => 2.54, 'm' => 100.0];

    public const TARGETS = ['product', 'category', 'brand'];

    public const MEDIA_OWNER = 'plg.size_chart';

    public function __construct(private readonly MediaDirectory $media) {}

    /**
     * @return array<int, string> id => tên (ngôn ngữ mặc định), cho ô chọn trong form
     */
    public function options(): array
    {
        return SizeChart::query()->orderBy('id')->get()
            ->mapWithKeys(fn (SizeChart $chart): array => [$chart->id => $chart->name(StoreLocale::default(), StoreLocale::default()).($chart->active ? '' : ' (tắt)')])
            ->all();
    }

    /**
     * @param  array{names: array<string, string|null>, base_unit: string, display_unit: ?string, blocks: list<array<string, mixed>>, advisor_enabled: bool, advisor_rules: list<array<string, mixed>>, is_default: bool, active: bool}  $data
     */
    public function save(array $data, ?SizeChart $chart = null, ?int $lockVersion = null): SizeChart
    {
        return DB::transaction(function () use ($data, $chart, $lockVersion): SizeChart {
            if ($chart !== null && $lockVersion !== null && $chart->lock_version !== $lockVersion) {
                throw ValidationException::withMessages(['lock_version' => 'Bảng size vừa được người khác sửa. Tải lại trang để xem bản mới.']);
            }
            $chart ??= new SizeChart;
            $chart->fill([
                'names' => array_filter($data['names'], fn (?string $name): bool => $name !== null && trim($name) !== ''),
                'base_unit' => $data['base_unit'],
                'display_unit' => $data['display_unit'] === $data['base_unit'] ? null : $data['display_unit'],
                'blocks' => self::normalizeBlocks($data['blocks']),
                'advisor_enabled' => $data['advisor_enabled'],
                'advisor_rules' => self::normalizeRules($data['advisor_rules']),
                'is_default' => $data['is_default'],
                'active' => $data['active'],
                'lock_version' => $chart->exists ? $chart->lock_version + 1 : 0,
            ])->save();

            if ($chart->is_default) {
                SizeChart::query()->whereKeyNot($chart->id)->where('is_default', true)->update(['is_default' => false]);
            }
            $this->media->syncUsages(self::MEDIA_OWNER, $chart->id, 'block', array_values(array_filter(array_map(
                fn (array $block): ?int => $block['type'] === 'image' ? (int) $block['media_id'] : null,
                $chart->blocks,
            ))));

            return $chart;
        });
    }

    public function delete(SizeChart $chart): void
    {
        DB::transaction(function () use ($chart): void {
            DB::table('plg_size_chart_assignments')->where('chart_id', $chart->id)->delete();
            $this->media->releaseUsages(self::MEDIA_OWNER, $chart->id);
            $chart->delete();
        });
    }

    public function assignedChart(string $target, int $targetId): ?int
    {
        $id = DB::table('plg_size_chart_assignments')->where('target_type', $target)->where('target_id', $targetId)->value('chart_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Gán (hoặc bỏ gán khi null) bảng cho một sản phẩm/danh mục/thương hiệu — mỗi đối tượng tối đa một bảng.
     */
    public function assign(string $target, int $targetId, ?int $chartId): void
    {
        if ($chartId === null) {
            DB::table('plg_size_chart_assignments')->where('target_type', $target)->where('target_id', $targetId)->delete();

            return;
        }
        DB::table('plg_size_chart_assignments')->updateOrInsert(['target_type' => $target, 'target_id' => $targetId], ['chart_id' => $chartId, 'updated_at' => now(), 'created_at' => now()]);
    }

    /**
     * Bảng cho một sản phẩm: gán trực tiếp → danh mục chính rồi các danh mục cha (gần nhất trước) → thương hiệu → mặc định.
     * Chỉ xét bảng đang bật.
     *
     * @param  list<int>  $categoryPath  id danh mục từ gốc tới danh mục chính
     */
    public function resolve(int $styleId, array $categoryPath, ?int $brandId): ?SizeChart
    {
        $candidates = [['product', $styleId], ...array_map(fn (int $id): array => ['category', $id], array_reverse($categoryPath))];
        if ($brandId !== null) {
            $candidates[] = ['brand', $brandId];
        }

        $assigned = DB::table('plg_size_chart_assignments')
            ->join('plg_size_charts', 'plg_size_charts.id', '=', 'plg_size_chart_assignments.chart_id')
            ->where('plg_size_charts.active', true)
            ->where(function ($query) use ($candidates): void {
                foreach ($candidates as [$type, $id]) {
                    $query->orWhere(fn ($match) => $match->where('target_type', $type)->where('target_id', $id));
                }
            })
            ->get(['target_type', 'target_id', 'chart_id'])
            ->mapWithKeys(fn (object $row): array => ["{$row->target_type}:{$row->target_id}" => (int) $row->chart_id]);

        foreach ($candidates as [$type, $id]) {
            if ($assigned->has("{$type}:{$id}")) {
                return SizeChart::query()->find($assigned->get("{$type}:{$id}"));
            }
        }

        return SizeChart::query()->where('active', true)->where('is_default', true)->first();
    }

    /**
     * Dữ liệu cho storefront/headless: khối đã chuẩn hoá (ảnh có URL, văn bản thành HTML an toàn, bảng có bản đổi đơn vị).
     *
     * @return array<string, mixed>
     */
    public function present(SizeChart $chart, string $locale): array
    {
        $ratio = $chart->display_unit === null ? null : self::UNITS[$chart->base_unit] / self::UNITS[$chart->display_unit];
        $mediaIds = array_values(array_filter(array_map(fn (array $block): ?int => $block['type'] === 'image' ? (int) $block['media_id'] : null, $chart->blocks)));
        $media = $mediaIds === [] ? [] : $this->media->find($mediaIds);

        $blocks = [];
        foreach ($chart->blocks as $block) {
            $blocks[] = match ($block['type']) {
                'image' => isset($media[$block['media_id']]) ? ['type' => 'image', 'url' => $media[$block['media_id']]->urlFor(800), 'alt' => (string) ($block['alt'] ?? '')] : null,
                'text' => ['type' => 'text', 'html' => self::markdown((string) $block['markdown'])],
                'table' => ['type' => 'table', 'rows' => $block['rows'], 'converted' => $ratio === null ? null : self::convert($block['rows'], $ratio)],
                default => null,
            };
        }

        return [
            'id' => $chart->id,
            'name' => $chart->name($locale, StoreLocale::default()),
            'unit' => $chart->base_unit,
            'display_unit' => $chart->display_unit,
            'blocks' => array_values(array_filter($blocks)),
            'advisor' => $chart->advisor_enabled && $chart->advisor_rules !== [] ? ['rules' => $chart->advisor_rules] : null,
        ];
    }

    /**
     * Đổi số trong bảng (trừ hàng đầu và cột đầu — tiêu đề) theo tỉ lệ, làm tròn 1 chữ số; ô không phải số giữ nguyên.
     *
     * @param  list<list<string>>  $rows
     * @return list<list<string>>
     */
    public static function convert(array $rows, float $ratio): array
    {
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $cell) {
                $value = str_replace(',', '.', trim($cell));
                if ($r > 0 && $c > 0 && is_numeric($value)) {
                    $rows[$r][$c] = (string) round((float) $value * $ratio, 1);
                }
            }
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     * @return list<array{size: string, height_min: float, height_max: float, weight_min: float, weight_max: float, note: string}>
     */
    public static function normalizeRules(array $rules): array
    {
        $result = [];
        foreach ($rules as $rule) {
            $size = trim((string) ($rule['size'] ?? ''));
            $bounds = [$rule['height_min'] ?? null, $rule['height_max'] ?? null, $rule['weight_min'] ?? null, $rule['weight_max'] ?? null];
            if ($size === '' || in_array(false, array_map('is_numeric', $bounds), true)) {
                continue;
            }
            [$heightMin, $heightMax, $weightMin, $weightMax] = array_map('floatval', $bounds);
            if ($heightMax < $heightMin || $weightMax < $weightMin) {
                continue;
            }
            $result[] = ['size' => $size, 'height_min' => $heightMin, 'height_max' => $heightMax, 'weight_min' => $weightMin, 'weight_max' => $weightMax, 'note' => trim((string) ($rule['note'] ?? ''))];
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function normalizeBlocks(array $blocks): array
    {
        $result = [];
        foreach ($blocks as $block) {
            $result[] = match ($block['type'] ?? null) {
                'image' => ['type' => 'image', 'media_id' => (int) $block['media_id'], 'alt' => trim((string) ($block['alt'] ?? ''))],
                'text' => ['type' => 'text', 'markdown' => trim((string) ($block['markdown'] ?? ''))],
                'table' => ['type' => 'table', 'rows' => array_values(array_map(fn ($row): array => array_values(array_map(fn ($cell): string => trim((string) $cell), (array) $row)), (array) ($block['rows'] ?? [])))],
                default => null,
            };
        }

        return array_values(array_filter($result, fn (?array $block): bool => $block !== null && ($block['type'] !== 'text' || $block['markdown'] !== '') && ($block['type'] !== 'table' || $block['rows'] !== [])));
    }

    /**
     * Markdown an toàn: bỏ HTML thô, chặn link không an toàn (như vani.cms).
     */
    public static function markdown(string $markdown): string
    {
        return (string) Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }
}
