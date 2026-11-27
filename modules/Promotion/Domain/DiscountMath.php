<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain;

use Modules\Shared\Domain\Money\Money;

/**
 * Phép tính giảm giá thuần: theo %, theo số tiền (phân bổ largest remainder), kẹp theo giá sàn.
 * Khoá mảng là khoá dòng (variant id); Money luôn >= 0 (độ lớn của giảm giá).
 */
final class DiscountMath
{
    /**
     * @param  array<int, Money>  $remaining  số tiền còn lại của từng dòng đủ điều kiện
     * @return array<int, Money>
     */
    public static function percentOff(array $remaining, int $basisPoints): array
    {
        return array_map(fn (Money $amount): Money => $amount->percentage(min(10_000, max(0, $basisPoints))), $remaining);
    }

    /**
     * Kẹp tổng giảm của một khuyến mãi về `max` (vd. "giảm 10%, tối đa 50.000đ"), chia lại theo tỷ trọng mức giảm của từng dòng.
     *
     * @param  array<int, Money>  $discounts
     * @return array<int, Money>
     */
    public static function capTotal(array $discounts, Money $max): array
    {
        $total = array_sum(array_map(fn (Money $money): int => max(0, $money->amount), $discounts));
        if ($total <= $max->amount) {
            return $discounts;
        }

        return self::amountOff($discounts, $max);
    }

    /**
     * Giảm một khoản cố định cho nhóm dòng, chia theo tỷ trọng số tiền còn lại; không vượt tổng còn lại.
     *
     * @param  array<int, Money>  $remaining
     * @return array<int, Money>
     */
    public static function amountOff(array $remaining, Money $amount): array
    {
        $keys = array_keys($remaining);
        $weights = array_map(fn (Money $money): int => max(0, $money->amount), array_values($remaining));
        $total = array_sum($weights);
        if ($keys === [] || $total === 0 || ! $amount->isPositive()) {
            return array_fill_keys($keys, Money::zero($amount->currency));
        }

        $capped = Money::of(min($amount->amount, $total), $amount->currency);

        return array_combine($keys, $capped->allocate($weights));
    }

    /**
     * Kẹp giảm giá để tổng giảm của mỗi dòng không vượt maxBasisPoints × thành tiền theo giá gốc (giá sàn). Dòng đang bán
     * dưới giá gốc (bảng giá sale/thành viên, giá gạch) đã dùng một phần trần: phần chênh giá gốc − giá bán được tính vào
     * (0.5.109) — flash sale −50% thì không còn chỗ cho mã giảm thêm khi trần là 50%.
     *
     * @param  array<int, Money>  $proposed  giảm giá mới đề xuất
     * @param  array<int, Money>  $lineSubtotals  thành tiền theo giá bán
     * @param  array<int, Money>  $alreadyDiscounted
     * @param  array<int, Money>  $referenceSubtotals  thành tiền theo giá gốc (thiếu = bằng giá bán)
     * @return array<int, Money>
     */
    public static function capToFloor(array $proposed, array $lineSubtotals, array $alreadyDiscounted, int $maxBasisPoints, array $referenceSubtotals = []): array
    {
        $result = [];
        foreach ($proposed as $key => $discount) {
            $reference = $referenceSubtotals[$key] ?? $lineSubtotals[$key];
            $priceDiscount = max(0, $reference->amount - $lineSubtotals[$key]->amount);
            $ceiling = $reference->percentage($maxBasisPoints, \RoundingMode::TowardsZero);
            $room = max(0, min($lineSubtotals[$key]->amount, $ceiling->amount - $priceDiscount) - ($alreadyDiscounted[$key]->amount ?? 0));
            $result[$key] = Money::of(min(max(0, $discount->amount), $room), $discount->currency);
        }

        return $result;
    }
}
