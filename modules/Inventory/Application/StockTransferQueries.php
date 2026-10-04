<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockTransfer;

/**
 * Truy vấn đọc cho màn hình chuyển kho trong Admin.
 */
final class StockTransferQueries
{
    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 100): array
    {
        $codes = Location::query()->pluck('code', 'id');

        return StockTransfer::query()->with('lines')->orderByDesc('id')->limit($limit)->get()
            ->map(fn (StockTransfer $transfer): array => [
                'id' => $transfer->id,
                'public_id' => $transfer->public_id,
                'from' => $codes[$transfer->from_location_id] ?? (string) $transfer->from_location_id,
                'to' => $codes[$transfer->to_location_id] ?? (string) $transfer->to_location_id,
                'status' => $transfer->status->value,
                'note' => $transfer->note,
                'reference' => $transfer->reference,
                'cancel_reason' => $transfer->cancel_reason,
                'items' => (int) $transfer->lines->sum('quantity'),
                'at' => ($transfer->received_at ?? $transfer->shipped_at ?? $transfer->created_at)?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'lines' => $transfer->lines->map(fn ($line): array => [
                    'variant_id' => (int) $line->variant_id,
                    'quantity' => (int) $line->quantity,
                    'received_quantity' => $line->received_quantity === null ? null : (int) $line->received_quantity,
                ])->all(),
            ])->all();
    }
}
