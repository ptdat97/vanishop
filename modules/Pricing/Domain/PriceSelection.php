<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain;

/**
 * Quy tắc chọn giá mặc định (price_list_priority), thuần PHP:
 * 1. Bảng giá priority cao nhất thắng; cùng priority → giá thấp hơn; vẫn trùng → id nhỏ hơn (ổn định).
 * 2. Giá gốc (compare_at): lấy compare_at của mức thắng nếu lớn hơn giá bán; nếu mức thắng không phải
 *    bảng base thì dùng giá base cao nhất khi lớn hơn giá bán. Không có thì null (không hiển thị giảm giá).
 *
 * @see docs/03-domains/catalog-pricing.md §6
 */
final class PriceSelection
{
    /**
     * @param  list<PriceCandidate>  $candidates
     */
    public static function choose(array $candidates): ?SelectedPrice
    {
        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (PriceCandidate $a, PriceCandidate $b): int => [$b->priority, $a->amount->amount, $a->priceListId]
            <=> [$a->priority, $b->amount->amount, $b->priceListId]);
        $winner = $candidates[0];

        $compareAt = null;
        if ($winner->compareAt !== null && $winner->compareAt->greaterThan($winner->amount)) {
            $compareAt = $winner->compareAt;
        } elseif ($winner->type !== PriceListType::Base) {
            foreach ($candidates as $candidate) {
                if ($candidate->type === PriceListType::Base && $candidate->amount->greaterThan($winner->amount)
                    && ($compareAt === null || $candidate->amount->greaterThan($compareAt))) {
                    $compareAt = $candidate->amount;
                }
            }
        }

        return new SelectedPrice($winner->amount, $compareAt, $winner->priceListId, $winner->priceListCode);
    }
}
