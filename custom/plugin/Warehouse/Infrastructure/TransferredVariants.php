<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Infrastructure;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * SKU có trong phiếu chuyển kho là "đã phát sinh": tắt ô trong ma trận biến thể chỉ chuyển ngừng bán (giữ phiếu).
 */
final class TransferredVariants implements VariantUsage
{
    public function code(): string
    {
        return 'vani-warehouse';
    }

    public function used(array $variantIds): array
    {
        return DB::table('plg_warehouse_transfer_lines')->whereIn('variant_id', $variantIds)->distinct()->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function release(array $variantIds): void {}
}
