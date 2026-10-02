<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockMovement;

/**
 * Truy vấn đọc cho màn hình tồn kho trong Admin.
 */
final class StockQueries
{
    /**
     * Mọi location (kể cả không phục vụ online — cửa hàng vẫn có tồn).
     *
     * @return list<Location>
     */
    public function locations(): array
    {
        return Location::query()
            ->orderByDesc('priority')->orderBy('code')
            ->get()->all();
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<string, array{on_hand: int, reserved: int, safety_stock: int, available: int}> "loc:variant" => số liệu
     */
    public function levels(array $variantIds): array
    {
        return DB::table('stock_levels')->whereIn('variant_id', $variantIds)->get()
            ->mapWithKeys(fn (object $row): array => [$row->location_id.':'.$row->variant_id => [
                'on_hand' => (int) $row->on_hand,
                'reserved' => (int) $row->reserved,
                'safety_stock' => (int) $row->safety_stock,
                'available' => (int) $row->on_hand - (int) $row->reserved - (int) $row->safety_stock,
            ]])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function movements(int $variantId, int $limit = 100): array
    {
        $codes = Location::query()->pluck('code', 'id');

        return StockMovement::query()->where('variant_id', $variantId)->orderByDesc('id')->limit($limit)->get()
            ->map(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'at' => $movement->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s'),
                'location' => $codes[$movement->location_id] ?? (string) $movement->location_id,
                'type' => $movement->type->value,
                'on_hand_delta' => $movement->on_hand_delta,
                'reserved_delta' => $movement->reserved_delta,
                'on_hand_after' => $movement->on_hand_after,
                'reserved_after' => $movement->reserved_after,
                'reason' => $movement->reason,
                'reference' => $movement->reference,
                'actor' => $movement->actor_type === null ? null : $movement->actor_type.($movement->actor_id !== null ? '#'.$movement->actor_id : ''),
            ])->all();
    }
}
