<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Application;

use Modules\Inventory\Contracts\LocationRegistry;
use Modules\Shared\Support\StoreClock;
use Plugin\Warehouse\Persistence\Transfer;

/**
 * Truy vấn đọc cho màn hình chuyển kho trong Admin.
 */
final class TransferQueries
{
    public function __construct(private readonly LocationRegistry $locations) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 100): array
    {
        $codes = [];
        foreach ($this->locations->all() as $location) {
            $codes[$location->id] = $location->code;
        }

        return Transfer::query()->with('lines')->orderByDesc('id')->limit($limit)->get()
            ->map(fn (Transfer $transfer): array => [
                'id' => $transfer->id,
                'public_id' => $transfer->public_id,
                'from' => $codes[$transfer->from_location_id] ?? (string) $transfer->from_location_id,
                'to' => $codes[$transfer->to_location_id] ?? (string) $transfer->to_location_id,
                'status' => $transfer->status->value,
                'note' => $transfer->note,
                'reference' => $transfer->reference,
                'cancel_reason' => $transfer->cancel_reason,
                'items' => (int) $transfer->lines->sum('quantity'),
                'at' => StoreClock::format($transfer->received_at ?? $transfer->shipped_at ?? $transfer->created_at),
                'lines' => $transfer->lines->map(fn ($line): array => [
                    'variant_id' => (int) $line->variant_id,
                    'quantity' => (int) $line->quantity,
                    'received_quantity' => $line->received_quantity === null ? null : (int) $line->received_quantity,
                ])->all(),
            ])->all();
    }
}
