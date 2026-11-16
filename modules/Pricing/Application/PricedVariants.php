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

    /**
     * SKU nằm trong bảng giá ngoài bảng niêm yết (khuyến mãi, thành viên…) là cấu hình thương mại có chủ đích → coi là đã phát
     * sinh (không xoá). Giá niêm yết đi theo SKU nên được dọn cùng SKU (lịch sử giá `price_history` vẫn giữ).
     */
    public function used(array $variantIds): array
    {
        return DB::table('prices')->join('price_lists', 'price_lists.id', '=', 'prices.price_list_id')
            ->whereIn('prices.variant_id', $variantIds)->where('price_lists.code', '!=', ListPriceSync::BASE_CODE)
            ->distinct()->pluck('prices.variant_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function release(array $variantIds): void
    {
        DB::table('prices')->whereIn('variant_id', $variantIds)->delete();
    }
}
