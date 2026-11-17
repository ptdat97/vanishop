<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Listeners;

use Modules\Catalog\Events\ProductDuplicated;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Nhân bản sản phẩm (stabilization A4): SKU mới nhận giá niêm yết (kèm giá gạch) của SKU nguồn — ghi qua `setPrices` nên có
 * lịch sử giá và PriceChanged. Không chép giá trong bảng giá sale/thành viên: bản nháp chưa thuộc đợt sale nào.
 */
final class CopyDuplicatedPrices
{
    public function __construct(private readonly PriceListService $lists) {}

    public function handle(ProductDuplicated $event): void
    {
        $base = PriceList::query()->where('code', 'base')->first();
        if ($base === null || $event->variantIds === []) {
            return;
        }
        $rows = Price::query()->where('price_list_id', $base->id)->where('min_qty', 1)->whereIn('variant_id', array_keys($event->variantIds))->get()
            ->map(fn (Price $price): array => ['variant_id' => $event->variantIds[$price->variant_id], 'amount' => $price->amount, 'compare_at_amount' => $price->compare_at_amount])
            ->values()->all();
        if ($rows !== []) {
            $this->lists->setPrices($base, $rows);
        }
    }
}
