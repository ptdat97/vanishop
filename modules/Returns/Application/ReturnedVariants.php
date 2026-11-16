<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * SKU có trong yêu cầu đổi/trả — SKU trả về hoặc SKU khách xin đổi sang (có thể chưa có đơn đổi) — là "đã phát sinh": không xoá
 * SKU khi tắt ô ma trận hay chuyển sản phẩm về sản phẩm đơn (giữ lịch sử đổi trả).
 */
final class ReturnedVariants implements VariantUsage
{
    public function code(): string
    {
        return 'returns';
    }

    public function used(array $variantIds): array
    {
        return DB::table('return_lines')
            ->where(fn ($query) => $query->whereIn('variant_id', $variantIds)->orWhereIn('exchange_variant_id', $variantIds))
            ->get(['variant_id', 'exchange_variant_id'])
            ->flatMap(fn (object $line): array => [(int) $line->variant_id, (int) $line->exchange_variant_id])
            ->filter(fn (int $id): bool => in_array($id, $variantIds, true))
            ->unique()->values()->all();
    }

    public function release(array $variantIds): void {}
}
