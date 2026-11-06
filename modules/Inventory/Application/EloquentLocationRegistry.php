<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Inventory\Contracts\Data\LocationData;
use Modules\Inventory\Contracts\LocationRegistry;
use Modules\Inventory\Persistence\Models\Location;

/**
 * LocationRegistry (0.3.88, ADR-042) trên bảng `locations` của Core; ghi qua LocationService (audit, optimistic lock).
 */
final class EloquentLocationRegistry implements LocationRegistry
{
    public function __construct(private readonly LocationService $locations) {}

    public function all(): array
    {
        return Location::query()->orderByDesc('priority')->orderBy('code')->get()->map(fn (Location $location): LocationData => self::toData($location))->all();
    }

    public function find(int $id): ?LocationData
    {
        $location = Location::query()->find($id);

        return $location === null ? null : self::toData($location);
    }

    public function save(array $data, ?int $id = null, ?int $expectedLockVersion = null): LocationData
    {
        $location = $id === null ? null : Location::query()->findOrFail($id);

        return self::toData($this->locations->save($data, $location, $expectedLockVersion)->refresh());
    }

    public static function toData(Location $location): LocationData
    {
        return new LocationData(
            id: $location->id, code: $location->code, name: $location->name, type: $location->type->value,
            address: $location->address, provinceCode: $location->province_code,
            shipsOnlineOrders: $location->ships_online_orders, allowsPickup: $location->allows_pickup, acceptsReturns: $location->accepts_returns,
            stockAuthority: $location->stock_authority, priority: $location->priority, status: $location->status, lockVersion: $location->lock_version,
        );
    }
}
