<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Danh sách sản phẩm Admin (0.3.103): cột "Giá bán" (giá niêm yết, khoảng giá khi các size khác giá, giá gạch) và thao tác
 * hàng loạt "Đổi giá" (slot vani.admin.product.list_columns / bulk_actions).
 */
final class ProductListPrices
{
    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * @param  list<int>  $styleIds
     * @return array{key: string, label: string, values: array<int, string>, edit_values: array<int, int|null>, urls: array<int, string>}|null
     */
    public function column(array $styleIds): ?array
    {
        if (! Gate::allows('pricing.view')) {
            return null;
        }
        $list = PriceList::query()->where('code', 'base')->first();
        $byStyle = $this->variants->ofStyles($styleIds);
        $variantIds = array_merge([], ...array_map(fn (array $variants): array => array_map(fn ($variant): int => $variant->id, $variants), array_values($byStyle)));
        $prices = $list === null ? collect() : Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->get()->keyBy('variant_id');
        $currency = (string) ($list->currency_code ?? config('vanishop.currency'));

        $values = [];
        $editValues = [];
        foreach ($byStyle as $styleId => $variants) {
            $found = array_values(array_filter(array_map(fn ($variant) => $prices->get($variant->id), $variants)));
            if ($found === []) {
                $values[$styleId] = 'Chưa có giá';
                $editValues[$styleId] = null;

                continue;
            }
            $amounts = array_map(fn (Price $price): int => $price->amount, $found);
            $compares = array_filter(array_map(fn (Price $price): ?int => $price->compare_at_amount, $found));
            $text = min($amounts) === max($amounts)
                ? $this->money->formatAmount(min($amounts), $currency)
                : $this->money->formatAmount(min($amounts), $currency).' – '.$this->money->formatAmount(max($amounts), $currency);
            if ($compares !== []) {
                $text .= "\ngạch ".$this->money->formatAmount(max($compares), $currency);
            }
            if (count($found) < count($variants)) {
                $text .= "\n".(count($variants) - count($found)).' biến thể chưa có giá';
            }
            $values[$styleId] = $text;
            $editValues[$styleId] = count($found) === count($variants) && min($amounts) === max($amounts)
                ? min($amounts)
                : null;
        }

        $urls = [];
        if (Gate::allows('pricing.manage')) {
            foreach (array_keys($byStyle) as $styleId) {
                $urls[$styleId] = route('admin.pricing.base-prices.style', ['styleId' => $styleId]);
            }
        }

        return ['key' => 'price', 'label' => 'Giá bán', 'values' => $values, 'edit_values' => $editValues, 'urls' => $urls];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bulkActions(): array
    {
        if (! Gate::allows('pricing.manage')) {
            return [];
        }

        return [[
            'key' => 'price', 'label' => 'Đổi giá', 'url' => route('admin.pricing.base-prices.bulk'), 'confirm' => true,
            'fields' => [
                ['key' => 'mode', 'label' => 'Cách đổi', 'required' => true, 'value' => 'percent_down', 'options' => [
                    ['value' => 'set', 'label' => 'Đặt giá mới (mọi size)'],
                    ['value' => 'percent_down', 'label' => 'Giảm theo %'],
                    ['value' => 'percent_up', 'label' => 'Tăng theo %'],
                    ['value' => 'amount_down', 'label' => 'Giảm số tiền'],
                    ['value' => 'clear_compare', 'label' => 'Bỏ giá gạch (kết thúc giảm giá)'],
                ]],
                ['key' => 'value', 'label' => 'Giá trị (₫ hoặc %; "Bỏ giá gạch" để 0)', 'required' => true, 'value' => '10'],
                ['key' => 'strike', 'label' => 'Khi giảm: hiện giá cũ gạch ngang', 'required' => true, 'value' => '1', 'options' => [
                    ['value' => '1', 'label' => 'Có'], ['value' => '0', 'label' => 'Không'],
                ]],
            ],
        ]];
    }
}
