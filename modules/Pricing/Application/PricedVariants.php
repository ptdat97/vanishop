<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * Ma trận biến thể (variant-matrix §3.2): giá không làm SKU "đã phát sinh"; SKU sắp bị xoá → gỡ giá của nó ở mọi bảng giá (lịch sử giá giữ lại).
 */
final class PricedVariants implements VariantUsage
{
    public function code(): string
    {
        return 'pricing';
    }

    public function used(array $variantIds): array
    {
        return [];
    }

    public function release(array $variantIds): void
    {
        DB::table('prices')->whereIn('variant_id', $variantIds)->delete();
    }
}
