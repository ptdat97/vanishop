<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Facades\Hook;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\StoreClock;

/**
 * Tổng quan giá đang chạy ở đầu trang Bảng giá (stabilization A5): đợt sale/thành viên đang chạy (sắp kết thúc), sắp bắt đầu;
 * SKU đang bán chưa có giá niêm yết; cảnh báo trên giá đang hiệu lực của các đợt đang chạy (vd. "Dưới giá vốn" của
 * `vani.cost-price`, qua hook `vani.pricing.bulk.warnings`) — kiểm tối đa WARNING_CHECK_LIMIT giá.
 */
final class PriceOverview
{
    public const ENDING_SOON_DAYS = 3;

    public const UPCOMING_DAYS = 7;

    public const WARNING_CHECK_LIMIT = 500;

    private const SAMPLES = 10;

    public function __construct(private readonly VariantDirectory $variants) {}

    /**
     * @return array{
     *     running: list<array{id: int, name: string, ends_at: string|null, ending_soon: bool, prices: int}>,
     *     upcoming: list<array{id: int, name: string, starts_at: string|null, prices: int}>,
     *     missing_base: array{count: int, samples: list<array{sku: string, product: string, style_id: int}>},
     *     warnings: array{checked: int, capped: bool, labels: array<string, int>, samples: list<array{sku: string, list: string, label: string}>}
     * }
     */
    public function summary(): array
    {
        $lists = PriceList::query()->withCount('prices')->where('type', '!=', PriceListType::Base)->where('status', 'active')->orderByDesc('priority')->get();
        $running = $lists->filter(fn (PriceList $list): bool => PriceListWorkspace::state($list) === 'running');
        $upcoming = $lists->filter(fn (PriceList $list): bool => PriceListWorkspace::state($list) === 'scheduled' && $list->starts_at !== null && $list->starts_at->lte(now()->addDays(self::UPCOMING_DAYS)));

        return [
            'running' => $running->map(fn (PriceList $list): array => [
                'id' => $list->id, 'name' => $list->name, 'ends_at' => StoreClock::format($list->ends_at), 'prices' => (int) $list->prices_count,
                'ending_soon' => $list->ends_at !== null && $list->ends_at->lte(now()->addDays(self::ENDING_SOON_DAYS)),
            ])->values()->all(),
            'upcoming' => $upcoming->map(fn (PriceList $list): array => ['id' => $list->id, 'name' => $list->name, 'starts_at' => StoreClock::format($list->starts_at), 'prices' => (int) $list->prices_count])->values()->all(),
            'missing_base' => $this->missingBase(),
            'warnings' => $this->warnings($running->all()),
        ];
    }

    /**
     * @return array{count: int, samples: list<array{sku: string, product: string, style_id: int}>}
     */
    private function missingBase(): array
    {
        $active = array_column(array_filter($this->variants->searchIndex(), fn (array $row): bool => $row['status'] === 'active'), 'id');
        $base = PriceList::query()->where('code', 'base')->value('id');
        $priced = $base === null ? [] : array_flip(Price::query()->where('price_list_id', $base)->where('min_qty', 1)->whereIn('variant_id', $active)->pluck('variant_id')->map(fn ($id): int => (int) $id)->all());
        $missing = array_values(array_filter($active, fn (int $id): bool => ! isset($priced[$id])));
        $found = $this->variants->find(array_slice($missing, 0, self::SAMPLES));

        return [
            'count' => count($missing),
            'samples' => array_values(array_map(fn ($variant): array => ['sku' => $variant->sku, 'product' => $variant->styleName, 'style_id' => $variant->styleId], $found)),
        ];
    }

    /**
     * @param  list<PriceList>  $running
     * @return array{checked: int, capped: bool, labels: array<string, int>, samples: list<array{sku: string, list: string, label: string}>}
     */
    private function warnings(array $running): array
    {
        $byId = collect($running)->keyBy('id');
        $prices = Price::query()->whereIn('price_list_id', $byId->keys())->where('min_qty', 1)->orderBy('id')->limit(self::WARNING_CHECK_LIMIT + 1)->get();
        $capped = $prices->count() > self::WARNING_CHECK_LIMIT;
        $prices = $prices->take(self::WARNING_CHECK_LIMIT);
        $labels = [];
        $hits = [];
        foreach ($prices as $price) {
            $list = $byId->get($price->price_list_id);
            foreach (array_unique(array_map('strval', (array) Hook::filter('vani.pricing.bulk.warnings', [], (int) $price->variant_id, null, (int) $price->amount, (string) $list?->code))) as $label) {
                $labels[$label] = ($labels[$label] ?? 0) + 1;
                $hits[] = ['variant_id' => (int) $price->variant_id, 'list' => (string) $list?->name, 'label' => $label];
            }
        }
        $hits = array_slice($hits, 0, self::SAMPLES);
        $found = $this->variants->find(array_column($hits, 'variant_id'));

        return [
            'checked' => $prices->count(), 'capped' => $capped, 'labels' => $labels,
            'samples' => array_map(fn (array $hit): array => ['sku' => $found[$hit['variant_id']]->sku ?? '#'.$hit['variant_id'], 'list' => $hit['list'], 'label' => $hit['label']], $hits),
        ];
    }
}
