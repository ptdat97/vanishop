<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;

/**
 * Location đang hoạt động và giao đơn online, theo thứ tự ưu tiên.
 */
final class OnlineLocations
{
    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return DB::table('locations')
            ->where('status', 'active')
            ->where('ships_online_orders', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
