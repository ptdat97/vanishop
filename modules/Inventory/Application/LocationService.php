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
