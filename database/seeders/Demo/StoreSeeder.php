<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Modules\Inventory\Application\LocationService;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Kho: "Kho chính" giao online (khi cửa hàng chưa có kho) + một cửa hàng nhận tại quầy để lưới tồn có hai cột.
 */
final class StoreSeeder extends Seeder
{
    public const STORE_CODE = 'CH-Q1';

    public function run(LocationService $locations): void
    {
        $locations->ensureDefault();

        if (! Location::query()->where('code', self::STORE_CODE)->exists()) {
            $locations->save([
                'code' => self::STORE_CODE, 'name' => 'Cửa hàng Quận 1', 'type' => 'store', 'address' => '12 Lê Lợi, Phường Bến Thành, TP. Hồ Chí Minh',
                'ships_online_orders' => false, 'allows_pickup' => true, 'accepts_returns' => true,
                'stock_authority' => Location::AUTHORITY_VANISHOP, 'priority' => 0, 'status' => 'active',
            ]);
        }
    }
}
