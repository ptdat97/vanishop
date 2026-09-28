<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockMovement;

final class ReturnService implements InventoryReturns
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function restock(int $locationId, int $variantId, int $quantity, string $reason, string $reference): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Số lượng nhập lại phải > 0.');
        }

        DB::transaction(function () use ($locationId, $variantId, $quantity, $reason, $reference): void {
            // Idempotent theo reference (vd. "shipment:{id}:returned") — webhook/job có thể chạy lại.
            if (StockMovement::query()->where('type', MovementType::Return)->where('reference', $reference)->where('variant_id', $variantId)->where('location_id', $locationId)->exists()) {
                return;
            }

            $location = Location::query()->findOrFail($locationId);
            $record = $this->ledger->lock([[$locationId, $variantId]], createMissing: true)["{$locationId}:{$variantId}"];
            $level = $record->toDomain();
            $after = $location->managesOnHand() ? $level->adjust($quantity) : $level;

            $this->ledger->save($record, $after, MovementType::Return, $reason, $reference);
            event(new AvailabilityChanged([$variantId]));
        });
    }
}
