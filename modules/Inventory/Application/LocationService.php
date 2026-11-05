<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Domain\LocationType;
use Modules\Inventory\Persistence\Models\Location;

final class LocationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public const DEFAULT_CODE = 'KHO-CHINH';

    /**
     * Cửa hàng chưa có kho nào → tạo "Kho chính" (giao đơn online, nhận hàng trả, VaniShop quản lý tồn). Đã có kho thì
     * không làm gì (null). Gọi khi cài đặt (`vani:inventory:ensure-location`) và từ nút ở trang tồn kho.
     */
    public function ensureDefault(): ?Location
    {
        if (Location::query()->exists()) {
            return null;
        }

        return $this->save([
            'code' => self::DEFAULT_CODE, 'name' => __('inventory::labels.default_location'), 'type' => LocationType::Warehouse,
            'ships_online_orders' => true, 'allows_pickup' => false, 'accepts_returns' => true,
            'stock_authority' => Location::AUTHORITY_VANISHOP, 'priority' => 0, 'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?Location $location = null, ?int $expectedLockVersion = null): Location
    {
        return DB::transaction(function () use ($data, $location, $expectedLockVersion): Location {
            if ($location !== null) {
                $updated = Location::query()->whereKey($location->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
                if ($updated === 0) {
                    throw ValidationException::withMessages(['lock_version' => __('inventory::messages.stale')]);
                }
            }

            $location ??= new Location;
            $location->fill($data)->save();

            $this->audit->record($location->wasRecentlyCreated ? 'inventory.location.created' : 'inventory.location.updated', 'location', $location->id, ['code' => $location->code]);

            return $location;
        });
    }
}
