<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Đổi giá niêm yết (bảng giá `base`) cho mọi biến thể của nhiều sản phẩm một lần (0.3.103, danh sách sản phẩm Admin):
 * đặt giá mới, tăng/giảm %, giảm số tiền, bỏ giá gạch. Làm tròn tới 1.000 ₫. `strike`: khi giảm giá, hiện giá cũ gạch
 * ngang (compare_at = giá cũ, nếu chưa có giá gạch cao hơn). Ghi qua PriceListService (audit, lịch sử giá, event PriceChanged).
 */
final class BulkBasePrice
{
    public const MODES = ['set', 'percent_down', 'percent_up', 'amount_down', 'clear_compare'];

    private const ROUND = 1000;

    public function __construct(
        private readonly PriceListService $lists,
        private readonly VariantDirectory $variants,
    ) {}

    public function baseList(): PriceList
    {
        return PriceList::query()->where('code', 'base')->first()
            ?? $this->lists->save(['code' => 'base', 'name' => 'Giá niêm yết', 'type' => 'base', 'priority' => 0, 'starts_at' => null, 'ends_at' => null, 'status' => 'active']);
    }

    /**
     * @param  list<int>  $styleIds
     * @return array{products: int, variants: int, skipped: int} skipped: biến thể chưa có giá (giảm/tăng % cần giá cũ)
     */
    public function apply(array $styleIds, string $mode, int $value, bool $strike): array
    {
        if (! in_array($mode, self::MODES, true)) {
            throw ValidationException::withMessages(['mode' => __('pricing::messages.bulk_mode_invalid')]);
        }
        if ($mode !== 'clear_compare' && ($value <= 0 || ($mode === 'percent_down' && $value >= 100))) {
            throw ValidationException::withMessages(['value' => __('pricing::messages.bulk_value_invalid')]);
        }

        $list = $this->baseList();
        $variantIds = [];
        $products = 0;
        foreach ($this->variants->ofStyles($styleIds) as $variants) {
            $products++;
            foreach ($variants as $variant) {
                $variantIds[] = $variant->id;
            }
        }
        $current = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->get()->keyBy('variant_id');

        $rows = [];
        $skipped = 0;
        foreach ($variantIds as $variantId) {
            $old = $current->get($variantId);
            $oldAmount = $old?->amount;
            $oldCompare = $old?->compare_at_amount;
            if ($mode !== 'set' && $oldAmount === null) {
                $skipped++;

                continue;
            }
            $amount = match ($mode) {
                'set' => $value,
                'percent_down' => self::round($oldAmount * (100 - $value) / 100),
                'percent_up' => self::round($oldAmount * (100 + $value) / 100),
                'amount_down' => $oldAmount - $value,
                'clear_compare' => $oldAmount,
            };
            if ($amount <= 0) {
                throw ValidationException::withMessages(['value' => __('pricing::messages.bulk_value_too_high')]);
            }
            $compare = match (true) {
                $mode === 'clear_compare' => null,
                $strike && $oldAmount !== null && $oldAmount > $amount => max($oldAmount, (int) $oldCompare),
                default => $oldCompare !== null && $oldCompare > $amount ? $oldCompare : null,
            };
            $rows[] = ['variant_id' => $variantId, 'amount' => $amount, 'compare_at_amount' => $compare];
        }
        $this->lists->setPrices($list, $rows);

        return ['products' => $products, 'variants' => count($rows), 'skipped' => $skipped];
    }

    private static function round(float $amount): int
    {
        return (int) (round($amount / self::ROUND) * self::ROUND);
    }
}
