<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Contracts\StockOverview;

/**
 * StockOverview trên read model `stock_grid_index` (db-refactor G3).
 */
final class StockGridOverview implements StockOverview
{
    public function stockedVariants(int $afterVariantId = 0, int $limit = 1000): array
    {
        return DB::table('stock_grid_index')->where('variant_id', '>', $afterVariantId)
            ->where('status', 'active')->where('tracked', true)->where('sellable', '>', 0)
            ->orderBy('variant_id')->limit(max(1, $limit))
            ->pluck('sellable', 'variant_id')->map(fn ($sellable): int => (int) $sellable)->all();
    }
}
