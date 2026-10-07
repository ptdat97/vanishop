<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Contracts\PriceImporter;
use Modules\Pricing\Persistence\Models\PriceList;

final class BasePriceImporter implements PriceImporter
{
    public function __construct(
        private readonly PriceListService $lists,
        private readonly VariantDirectory $variants,
    ) {}

    public function setBasePrices(array $prices): array
    {
        if ($prices === []) {
            return ['updated' => 0, 'unknown' => []];
        }

        $list = PriceList::query()->where('code', 'base')->first()
            ?? $this->lists->save(['code' => 'base', 'name' => 'Giá niêm yết', 'type' => 'base', 'priority' => 0, 'starts_at' => null, 'ends_at' => null, 'status' => 'active']);
        $variants = $this->variants->findBySkus(array_map('strval', array_keys($prices)));

        $rows = [];
        $unknown = [];
        foreach ($prices as $sku => $price) {
            $variant = $variants[(string) $sku] ?? null;
            if ($variant === null) {
                $unknown[] = (string) $sku;

                continue;
            }
            $rows[] = ['variant_id' => $variant->id, 'amount' => $price['amount'], 'compare_at_amount' => $price['compare_at'] ?? null];
        }

        return ['updated' => $rows === [] ? 0 : $this->lists->setPrices($list, $rows), 'unknown' => $unknown];
    }
}
