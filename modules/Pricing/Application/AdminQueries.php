<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Truy vấn đọc cho màn hình Admin của Pricing — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    public function priceList(int $id): PriceList
    {
        return PriceList::query()->findOrFail($id);
    }

    /**
     * @return Collection<int, PriceList>
     */
    public function priceListsWithUsage(): Collection
    {
        return PriceList::query()->withCount('prices')->orderByDesc('priority')->orderBy('code')->get();
    }

    /**
     * Lịch sử đổi giá (A5), mới nhất trước. `$variantIds` null = mọi SKU.
     *
     * @param  list<int>|null  $variantIds
     * @return LengthAwarePaginator<int, PriceHistory>
     */
    public function priceHistory(Builder|array|null $variantIds, ?int $priceListId, int $perPage): LengthAwarePaginator
    {
        return PriceHistory::query()
            ->when($variantIds !== null, fn ($query) => $query->whereIn('variant_id', $variantIds))
            ->when($priceListId !== null, fn ($query) => $query->where('price_list_id', $priceListId))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, PriceList> id => bảng giá (tên, tiền tệ)
     */
    public function priceListsById(): Collection
    {
        return PriceList::query()->orderByDesc('priority')->orderBy('code')->get(['id', 'name', 'currency_code'])->keyBy('id');
    }
}
