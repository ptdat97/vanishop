<?php

declare(strict_types=1);

namespace Plugin\CostPrice\Infrastructure;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantUsage;

/**
 * Giá vốn đi theo SKU (cấu hình, không phải lịch sử thương mại): không giữ SKU lại, nhưng dọn trước khi Catalog xoá SKU —
 * `plg_cost_prices.variant_id` khoá ngoại restrict, không dọn thì xoá SKU (tắt ô ma trận, về sản phẩm đơn) lỗi.
 */
final class CostPriceUsage implements VariantUsage
{
    public function code(): string
    {
        return 'vani.cost-price';
    }

    public function used(array $variantIds): array
    {
        return [];
    }

    public function release(array $variantIds): void
    {
        DB::table('plg_cost_prices')->whereIn('variant_id', $variantIds)->delete();
    }
}
