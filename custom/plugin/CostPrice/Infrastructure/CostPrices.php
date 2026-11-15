<?php

declare(strict_types=1);

namespace Plugin\CostPrice\Infrastructure;

use Illuminate\Support\Facades\DB;

/**
 * Đọc/ghi giá vốn theo SKU (bảng `plg_cost_prices`).
 */
final class CostPrices
{
    /**
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    public function of(array $variantIds): array
    {
        return $variantIds === [] ? [] : DB::table('plg_cost_prices')->whereIn('variant_id', $variantIds)->pluck('amount', 'variant_id')->map(fn ($amount): int => (int) $amount)->all();
    }

    public function set(int $variantId, int $amount): void
    {
        DB::table('plg_cost_prices')->updateOrInsert(['variant_id' => $variantId], ['amount' => $amount, 'updated_at' => now(), 'created_at' => now()]);
    }
}
