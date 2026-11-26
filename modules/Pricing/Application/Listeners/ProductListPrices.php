<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Application\PriceListWorkspace;
use Modules\Pricing\Domain\PriceListType;
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
        private readonly PriceListWorkspace $workspace,
    ) {}

    /**
     * @param  list<int>  $productIds
     * @return array{key: string, label: string, values: array<int, string>, edit_values: array<int, int|null>, urls: array<int, string>}|null
     */
    public function column(array $productIds): ?array
    {
        if (! Gate::allows('pricing.view')) {
            return null;
        }
        $list = PriceList::query()->where('code', 'base')->first();
        $byProduct = $this->variants->ofProducts($productIds);
        $variantIds = array_merge([], ...array_map(fn (array $variants): array => array_map(fn ($variant): int => $variant->id, $variants), array_values($byProduct)));
        $prices = $list === null ? collect() : Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->get()->keyBy('variant_id');
        $currency = (string) ($list->currency_code ?? config('vanishop.currency'));

        $values = [];
        $editValues = [];
        foreach ($byProduct as $productId => $variants) {
            $found = array_values(array_filter(array_map(fn ($variant) => $prices->get($variant->id), $variants)));
            if ($found === []) {
                $values[$productId] = __('pricing::texts.product_list_prices.no_price_yet');
                $editValues[$productId] = null;

                continue;
            }
            $amounts = array_map(fn (Price $price): int => $price->amount, $found);
            $compares = array_filter(array_map(fn (Price $price): ?int => $price->compare_at_amount, $found));
            $text = min($amounts) === max($amounts)
                ? $this->money->formatAmount(min($amounts), $currency)
                : $this->money->formatAmount(min($amounts), $currency).' – '.$this->money->formatAmount(max($amounts), $currency);
            if ($compares !== []) {
                $text .= __('pricing::texts.product_list_prices.compare').$this->money->formatAmount(max($compares), $currency);
            }
            if (count($found) < count($variants)) {
                $text .= "\n".(count($variants) - count($found)).__('pricing::texts.product_list_prices.variants_without_price');
            }
            $values[$productId] = $text;
            $editValues[$productId] = count($found) === count($variants) && min($amounts) === max($amounts)
                ? min($amounts)
                : null;
        }

        $urls = [];
        if (Gate::allows('pricing.manage')) {
            foreach (array_keys($byProduct) as $productId) {
                $urls[$productId] = route('admin.pricing.base-prices.product', ['productId' => $productId]);
            }
        }

        return ['key' => 'price', 'label' => __('pricing::texts.product_list_prices.selling_price'), 'values' => $values, 'edit_values' => $editValues, 'urls' => $urls];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bulkActions(): array
    {
        if (! Gate::allows('pricing.manage')) {
            return [];
        }

        $lists = PriceList::query()->where('type', '!=', PriceListType::Base)->orderByDesc('id')->get();
        $addToList = $lists->isEmpty() ? [] : [$this->applyListAction($lists->all())];

        return [...$addToList, [
            'key' => 'price', 'label' => __('pricing::texts.product_list_prices.change_price'), 'url' => route('admin.pricing.base-prices.bulk'), 'confirm' => true,
            // A5: xem trước số SKU, khoảng giá cũ/mới, cảnh báo (giá vốn…) trước khi ghi.
            'preview_url' => route('admin.pricing.base-prices.bulk.preview'),
            'fields' => [
                ['key' => 'mode', 'label' => __('pricing::texts.product_list_prices.how_change'), 'required' => true, 'value' => 'percent_down', 'options' => [
                    ['value' => 'set', 'label' => __('pricing::texts.product_list_prices.set_new_price_every_size')],
                    ['value' => 'percent_down', 'label' => __('pricing::texts.product_list_prices.decrease')],
                    ['value' => 'percent_up', 'label' => __('pricing::texts.product_list_prices.increase')],
                    ['value' => 'amount_down', 'label' => __('pricing::texts.product_list_prices.decrease_amount')],
                    ['value' => 'clear_compare', 'label' => __('pricing::texts.product_list_prices.remove_compare_price_end_discount')],
                ]],
                ['key' => 'value', 'label' => __('pricing::texts.product_list_prices.value_0_remove_compare_price'), 'required' => true, 'value' => '10'],
                ['key' => 'strike', 'label' => __('pricing::texts.product_list_prices.when_decreasing_show_old_price'), 'required' => true, 'value' => '1', 'options' => [
                    ['value' => '1', 'label' => __('pricing::texts.product_list_prices.yes')], ['value' => '0', 'label' => __('pricing::texts.product_list_prices.no')],
                ]],
            ],
        ]];
    }

    /**
     * @param  list<PriceList>  $lists
     * @return array<string, mixed>
     */
    private function applyListAction(array $lists): array
    {
        $options = $this->workspace->pluginOptions();
        $modes = (array) __('pricing::labels.bulk.modes');

        return [
            'key' => 'add_to_price_list', 'label' => __('pricing::texts.product_list_prices.apply_price_list'),
            'url' => route('admin.pricing.price-lists.bulk-from-products'),
            'confirm' => false,
            'preview_url' => route('admin.pricing.price-lists.bulk-from-products.preview'),
            'fields' => [
                ['key' => 'price_list_id', 'label' => __('pricing::texts.product_list_prices.price_lists'), 'required' => true,
                    'options' => array_map(fn (PriceList $list): array => ['value' => (string) $list->id, 'label' => $list->name.' · '.__('pricing::labels.price_list_state.'.PriceListWorkspace::state($list))], $lists)],
                ['key' => 'mode', 'label' => __('pricing::texts.product_list_prices.how_change'), 'required' => true, 'value' => 'percent',
                    'options' => [
                        ...array_map(fn (string $code, string $label): array => ['value' => $code, 'label' => $label], array_keys($modes), array_values($modes)),
                        ...array_map(fn (array $mode): array => ['value' => $mode['code'], 'label' => $mode['label']], $options['modes']),
                    ]],
                ['key' => 'value', 'label' => __('pricing::texts.product_list_prices.list_rule_value'), 'required' => true],
                ['key' => 'round_to', 'label' => __('pricing::admin.price_lists_prices.rounding'), 'required' => true, 'value' => '1000',
                    'options' => [
                        ['value' => '1000', 'label' => __('pricing::admin.price_lists_prices.round_1_000')],
                        ['value' => '0', 'label' => __('pricing::texts.product_list_prices.no_rounding')],
                    ]],
                ...($options['roundings'] === [] ? [] : [['key' => 'rounding', 'label' => __('pricing::texts.product_list_prices.plugin_rounding'),
                    'options' => array_map(fn (array $rounding): array => ['value' => $rounding['code'], 'label' => $rounding['label']], $options['roundings'])]]),
            ],
        ];
    }
}
