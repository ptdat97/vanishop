<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * Ma trận biến thể (variant-matrix §3.2): SKU có biến động/tồn/giữ hàng/đối soát là "đã phát sinh" (chỉ ngừng bán); SKU sắp bị xoá → dọn dòng tồn bằng 0.
 */
final class StockedVariants implements VariantUsage
{
    public function code(): string
    {
        return 'inventory';
    }

    public function used(array $variantIds): array
    {
        return collect()
            ->merge(DB::table('stock_movements')->whereIn('variant_id', $variantIds)->distinct()->pluck('variant_id'))
            ->merge(DB::table('stock_levels')->whereIn('variant_id', $variantIds)->where(fn ($query) => $query->where('on_hand', '!=', 0)->orWhere('reserved', '!=', 0))->pluck('variant_id'))
            ->merge(DB::table('inventory_reconciliation_lines')->whereIn('variant_id', $variantIds)->pluck('variant_id'))
            ->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }

    public function release(array $variantIds): void
    {
        DB::table('stock_levels')->whereIn('variant_id', $variantIds)->where('on_hand', 0)->where('reserved', 0)->delete();
    }
}
