<?php

declare(strict_types=1);

namespace Plugin\CostPrice\Infrastructure;

use Modules\Pricing\Contracts\BulkPriceMode;

/**
 * "Giá vốn + %" (EP v3 X4 — tham chiếu `BulkPriceMode`): giá mới = giá vốn × (1 + value%). SKU chưa có giá vốn: bỏ qua.
 */
final class CostMarkupMode implements BulkPriceMode
{
    public function __construct(private readonly CostPrices $costs) {}

    public function code(): string
    {
        return 'cost_markup';
    }

    public function label(): string
    {
        return __('vani-cost-price::texts.cost_markup_mode.cost_price');
    }

    public function hint(): string
    {
        return __('vani-cost-price::texts.cost_markup_mode.added_cost_price');
    }

    public function compute(array $variantIds, float $value, array $listAmounts): array
    {
        $costs = $this->costs->of($variantIds);

        return array_map(fn (int $id): ?int => isset($costs[$id]) ? (int) round($costs[$id] * (100 + $value) / 100) : null, array_combine($variantIds, $variantIds));
    }
}
