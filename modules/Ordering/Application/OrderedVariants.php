<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * SKU đã có trong đơn hàng là "đã phát sinh": tắt ô trong ma trận biến thể chỉ chuyển ngừng bán (giữ lịch sử đơn).
 */
final class OrderedVariants implements VariantUsage
{
    public function code(): string
    {
        return 'ordering';
    }

    public function used(array $variantIds): array
    {
        return DB::table('order_lines')->whereIn('variant_id', $variantIds)->distinct()->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function release(array $variantIds): void {}
}
