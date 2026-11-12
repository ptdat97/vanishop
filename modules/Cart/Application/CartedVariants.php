<?php

declare(strict_types=1);

namespace Modules\Cart\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * SKU có trong giỏ (kể cả giỏ đã đặt/đã gộp — dòng giỏ giữ khoá ngoại tới SKU) là "đã phát sinh": chỉ chuyển ngừng bán.
 */
final class CartedVariants implements VariantUsage
{
    public function code(): string
    {
        return 'cart';
    }

    public function used(array $variantIds): array
    {
        return DB::table('cart_lines')->whereIn('variant_id', $variantIds)->distinct()->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function release(array $variantIds): void {}
}
