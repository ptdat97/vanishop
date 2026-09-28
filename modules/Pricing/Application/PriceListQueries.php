<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Facades\DB;

/**
 * Truy vấn đọc cho màn hình quản trị bảng giá.
 */
final class PriceListQueries
{
    /**
     * @return list<int>
     */
    public function channelIds(int $priceListId): array
    {
        return DB::table('channel_price_lists')->where('price_list_id', $priceListId)->orderBy('channel_id')
            ->pluck('channel_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  list<int>  $priceListIds
     * @return array<int, int> price list id => số kênh
     */
    public function channelCounts(array $priceListIds): array
    {
        return DB::table('channel_price_lists')->whereIn('price_list_id', $priceListIds)->groupBy('price_list_id')
            ->selectRaw('price_list_id, count(*) as total')->pluck('total', 'price_list_id')
            ->mapWithKeys(fn ($total, $id): array => [(int) $id => (int) $total])->all();
    }
}
