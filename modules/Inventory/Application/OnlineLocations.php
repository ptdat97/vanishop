<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Persistence\Models\Location;

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

    /**
     * Location giao online đầu tiên (theo ưu tiên) do VaniShop quản lý tồn vật lý — nơi bù tồn cho sản phẩm không theo dõi tồn.
     * Location do hệ thống ngoài quản lý (ERP/POS) không được chỉnh tồn ở VaniShop; không có thì null.
     */
    public function topUpLocation(): ?int
    {
        $id = DB::table('locations')
            ->where('status', 'active')
            ->where('ships_online_orders', true)
            ->where('stock_authority', Location::AUTHORITY_VANISHOP)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }
}
