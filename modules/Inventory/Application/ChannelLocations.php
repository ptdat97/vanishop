<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;

/**
 * Location phục vụ đơn online của một kênh cho một brand, theo thứ tự ưu tiên.
 */
final class ChannelLocations
{
    /**
     * @return list<int>
     */
    public function forBrand(int $channelId, int $brandId): array
    {
        return DB::table('channel_locations')
            ->join('locations', 'locations.id', '=', 'channel_locations.location_id')
            ->join('location_brands', 'location_brands.location_id', '=', 'locations.id')
            ->where('channel_locations.channel_id', $channelId)
            ->where('location_brands.brand_id', $brandId)
            ->where('locations.status', 'active')
            ->where('locations.ships_online_orders', true)
            ->orderByDesc('locations.priority')
            ->orderBy('locations.id')
            ->pluck('locations.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
