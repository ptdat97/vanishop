<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Modules\Pricing\Contracts\ListPriceReader;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;

final class BaseListPriceReader implements ListPriceReader
{
    public function forVariants(array $variantIds): array
    {
        $listId = $this->listId();
        if ($listId === null || $variantIds === []) {
            return [];
        }

        return Price::query()->where('price_list_id', $listId)->where('min_qty', 1)->whereIn('variant_id', array_values(array_unique($variantIds)))
            ->pluck('amount', 'variant_id')->map(fn (mixed $amount): int => (int) $amount)->all();
    }

    public function listId(): ?int
    {
        $id = PriceList::query()->where('code', ListPriceSync::BASE_CODE)->value('id');

        return $id === null ? null : (int) $id;
    }
}
