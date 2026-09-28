<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Điều chỉnh tay, kiểm kê (đặt số tuyệt đối) và tồn an toàn. Chỉ cho location do VaniShop quản lý tồn vật lý.
 */
final class StockAdjustmentService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly AuditLogger $audit,
    ) {}

    public function adjust(Location $location, int $variantId, int $delta, string $reason): void
    {
        $this->guardAuthority($location);

        DB::transaction(function () use ($location, $variantId, $delta, $reason): void {
            $record = $this->ledger->lock([[$location->id, $variantId]], createMissing: true)["{$location->id}:{$variantId}"];

            try {
                $after = $record->toDomain()->adjust($delta);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
            }

            $this->ledger->save($record, $after, MovementType::Adjust, $reason);
            $this->audit->record('inventory.stock.adjusted', 'stock_level', $record->id, ['location' => $location->code, 'variant_id' => $variantId, 'delta' => $delta, 'reason' => $reason]);
            event(new StockAdjusted($location->id, $variantId, $delta, $reason));
            event(new AvailabilityChanged([$variantId]));
        }, attempts: 3);
    }

    /**
     * Kiểm kê: đặt tồn vật lý về số đếm được.
     */
    public function count(Location $location, int $variantId, int $onHand, string $reason): void
    {
        $this->guardAuthority($location);

        DB::transaction(function () use ($location, $variantId, $onHand, $reason): void {
            $record = $this->ledger->lock([[$location->id, $variantId]], createMissing: true)["{$location->id}:{$variantId}"];
            $before = $record->on_hand;

            try {
                $after = $record->toDomain()->sync($onHand);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
            }

            if ($after === null || $after->onHand === $before) {
                return;
            }

            $this->ledger->save($record, $after, MovementType::Sync, $reason);
            $this->audit->record('inventory.stock.counted', 'stock_level', $record->id, ['location' => $location->code, 'variant_id' => $variantId, 'from' => $before, 'to' => $onHand]);
            event(new StockAdjusted($location->id, $variantId, $onHand - $before, $reason));
            event(new AvailabilityChanged([$variantId]));
        }, attempts: 3);
    }

    public function setSafetyStock(Location $location, int $variantId, int $safetyStock): void
    {
        DB::transaction(function () use ($location, $variantId, $safetyStock): void {
            $record = $this->ledger->lock([[$location->id, $variantId]], createMissing: true)["{$location->id}:{$variantId}"];
            if ($record->safety_stock === $safetyStock) {
                return;
            }

            $this->ledger->save($record, $record->toDomain()->withSafetyStock($safetyStock), MovementType::SafetyStock, "safety_stock={$safetyStock}");
            $this->audit->record('inventory.stock.safety_stock', 'stock_level', $record->id, ['location' => $location->code, 'variant_id' => $variantId, 'safety_stock' => $safetyStock]);
            event(new AvailabilityChanged([$variantId]));
        });
    }

    private function guardAuthority(Location $location): void
    {
        if (! $location->managesOnHand()) {
            throw ValidationException::withMessages(['location_id' => __('inventory::messages.external_authority', ['authority' => $location->stock_authority])]);
        }
    }
}
