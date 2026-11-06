<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Contracts\StockMovementRejected;
use Modules\Inventory\Contracts\StockMovements;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Persistence\Models\Location;

/**
 * StockMovements (0.3.88, ADR-042): xuất/nhập chuyển kho qua sổ biến động — engine ở Core, chứng từ ở `vani.warehouse`.
 */
final class StockMovementService implements StockMovements
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function transferOut(int $locationId, array $quantities, string $reason, string $reference): void
    {
        $this->move($locationId, $quantities, MovementType::TransferOut, $reason, $reference);
    }

    public function transferIn(int $locationId, array $quantities, string $reason, string $reference): void
    {
        $this->move($locationId, $quantities, MovementType::TransferIn, $reason, $reference);
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function move(int $locationId, array $quantities, MovementType $type, string $reason, string $reference): void
    {
        $location = Location::query()->find($locationId);
        if ($location === null) {
            throw new StockMovementRejected(__('inventory::messages.location_missing'));
        }
        if (! $location->managesOnHand()) {
            throw new StockMovementRejected(__('inventory::messages.transfer_external_authority'));
        }
        $quantities = array_filter(array_map('intval', $quantities), fn (int $quantity): bool => $quantity > 0);
        if ($quantities === []) {
            return;
        }
        ksort($quantities);

        DB::transaction(function () use ($location, $quantities, $type, $reason, $reference): void {
            $records = $this->ledger->lock(array_map(fn (int $variantId): array => [$location->id, $variantId], array_keys($quantities)), createMissing: true);
            foreach ($quantities as $variantId => $quantity) {
                $record = $records["{$location->id}:{$variantId}"];
                try {
                    $after = $type === MovementType::TransferOut ? $record->toDomain()->transferOut($quantity) : $record->toDomain()->transferIn($quantity);
                } catch (InvalidArgumentException $exception) {
                    throw new StockMovementRejected($exception->getMessage(), previous: $exception);
                }
                $this->ledger->save($record, $after, $type, $reason, $reference);
            }
            event(new AvailabilityChanged(array_keys($quantities)));
        }, attempts: 3);
    }
}
