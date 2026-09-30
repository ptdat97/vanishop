<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Contracts\Data\SyncOutcome;
use Modules\Inventory\Contracts\InventorySync;
use Modules\Inventory\Contracts\NotStockAuthority;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Đồng bộ on_hand từ authority ngoài. Idempotent theo version: gửi lại cùng version → Stale, không ghi thêm.
 */
final class AuthoritySyncService implements InventorySync
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function assertAuthority(string $authority, array $locationCodes): void
    {
        $owned = Location::query()->whereIn('code', $locationCodes)->where('stock_authority', $authority)->pluck('code')->all();
        foreach ($locationCodes as $code) {
            if ($authority === Location::AUTHORITY_VANISHOP || ! in_array($code, $owned, true)) {
                throw new NotStockAuthority($code);
            }
        }
    }

    public function syncOnHand(string $authority, string $locationCode, int $variantId, int $onHand, int $version, string $reason): SyncOutcome
    {
        $location = Location::query()->where('code', $locationCode)->first();
        if ($location === null || $location->managesOnHand() || $location->stock_authority !== $authority) {
            throw new NotStockAuthority($locationCode);
        }

        return DB::transaction(function () use ($location, $variantId, $onHand, $version, $reason): SyncOutcome {
            $record = $this->ledger->lock([[$location->id, $variantId]], createMissing: true)["{$location->id}:{$variantId}"];
            $before = $record->on_hand;

            $after = $record->toDomain()->sync($onHand, $version);
            if ($after === null) {
                return SyncOutcome::Stale;
            }

            if ($after->onHand === $before) {
                $record->fillFromDomain($after)->save();

                return SyncOutcome::Unchanged;
            }

            $this->ledger->save($record, $after, MovementType::Sync, $reason, "v{$version}");
            event(new StockAdjusted($location->id, $variantId, $onHand - $before, $reason));
            event(new AvailabilityChanged([$variantId]));

            return SyncOutcome::Applied;
        }, attempts: 3);
    }
}
