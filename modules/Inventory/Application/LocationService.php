<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Persistence\Models\Location;

final class LocationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $brandIds
     * @param  list<int>  $channelIds
     */
    public function save(array $data, array $brandIds, array $channelIds, ?Location $location = null, ?int $expectedLockVersion = null): Location
    {
        return DB::transaction(function () use ($data, $brandIds, $channelIds, $location, $expectedLockVersion): Location {
            if ($location !== null) {
                $updated = Location::query()->whereKey($location->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
                if ($updated === 0) {
                    throw ValidationException::withMessages(['lock_version' => __('inventory::messages.stale')]);
                }
            }

            $location ??= new Location;
            $location->fill($data)->save();

            DB::table('location_brands')->where('location_id', $location->id)->delete();
            DB::table('location_brands')->insert(array_map(fn (int $brandId): array => ['location_id' => $location->id, 'brand_id' => $brandId], array_values(array_unique($brandIds))));
            DB::table('channel_locations')->where('location_id', $location->id)->delete();
            DB::table('channel_locations')->insert(array_map(fn (int $channelId): array => ['channel_id' => $channelId, 'location_id' => $location->id], array_values(array_unique($channelIds))));

            $this->audit->record($location->wasRecentlyCreated ? 'inventory.location.created' : 'inventory.location.updated', 'location', $location->id, ['code' => $location->code, 'brands' => $brandIds, 'channels' => $channelIds]);

            return $location;
        });
    }

    /**
     * @return array{brand_ids: list<int>, channel_ids: list<int>}
     */
    public function assignments(int $locationId): array
    {
        return [
            'brand_ids' => DB::table('location_brands')->where('location_id', $locationId)->orderBy('brand_id')->pluck('brand_id')->map(fn ($id): int => (int) $id)->all(),
            'channel_ids' => DB::table('channel_locations')->where('location_id', $locationId)->orderBy('channel_id')->pluck('channel_id')->map(fn ($id): int => (int) $id)->all(),
        ];
    }
}
