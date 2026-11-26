<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Truy vấn SKU trên read model `price_variant_index` (bí danh `i`, db-refactor L55): tìm "chứa" theo SKU, mã, tên sản phẩm
 * (không phân biệt hoa thường), sắp theo thứ tự hiển thị của Catalog (tên → mã → sản phẩm → vị trí SKU).
 */
final class PriceVariantSearch
{
    public function matching(string $text = ''): Builder
    {
        $text = mb_strtolower(trim($text));

        return DB::table('price_variant_index as i')
            ->when($text !== '', fn (Builder $query) => $query->where('i.search_text', 'like', '%'.addcslashes($text, '%_\\').'%'));
    }

    public function ordered(Builder $query): Builder
    {
        return $query->orderBy('i.name_sort')->orderBy('i.product_code')->orderBy('i.product_id')->orderBy('i.variant_rank');
    }
}
