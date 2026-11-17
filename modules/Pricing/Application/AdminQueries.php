<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Database\Eloquent\Collection;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Truy vấn đọc cho màn hình Admin của Pricing — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /**
     * @return Collection<int, PriceList>
     */
    public function priceListsWithUsage(): Collection
    {
        return PriceList::query()->withCount('prices')->orderByDesc('priority')->orderBy('code')->get();
    }
}
