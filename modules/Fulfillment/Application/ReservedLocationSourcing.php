<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Modules\Fulfillment\Contracts\Data\AllocationProposal;
use Modules\Fulfillment\Contracts\Data\SourcingRequest;
use Modules\Fulfillment\Contracts\SourcingStrategy;

/**
 * Giao từ đúng location đã giữ hàng lúc đặt (Inventory đã chọn theo priority). Một shipment mỗi location.
 */
final class ReservedLocationSourcing implements SourcingStrategy
{
    public function code(): string
    {
        return 'reserved_locations';
    }

    public function allocate(SourcingRequest $request): array
    {
        $remaining = [];
        foreach ($request->lines as $line) {
            $remaining[$line->id] = ['variant' => $line->variantId, 'quantity' => $line->quantity];
        }

        $byLocation = [];
        foreach ($request->reserved as $reserved) {
            $quantity = $reserved->quantity;
            foreach ($remaining as $lineId => &$line) {
                if ($quantity === 0 || $line['variant'] !== $reserved->variantId || $line['quantity'] === 0) {
                    continue;
                }
                $take = min($quantity, $line['quantity']);
                $byLocation[$reserved->locationId][$lineId] = ($byLocation[$reserved->locationId][$lineId] ?? 0) + $take;
                $line['quantity'] -= $take;
                $quantity -= $take;
            }
            unset($line);
        }

        ksort($byLocation);

        return array_map(fn (int $locationId, array $lines): AllocationProposal => new AllocationProposal($locationId, $lines), array_keys($byLocation), array_values($byLocation));
    }
}
