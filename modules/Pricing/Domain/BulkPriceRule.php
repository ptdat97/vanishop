<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain;

use InvalidArgumentException;

/**
 * Cách tính giá hàng loạt cho một bảng giá (trang làm việc bảng giá, đợt 1): từ giá niêm yết của SKU.
 *   `percent` — giảm theo phần trăm (`value` tính bằng basis point: 3000 = 30%);
 *   `amount`  — giảm một số tiền;
 *   `fixed`   — đồng giá (không cần giá niêm yết).
 * Kết quả không âm; làm tròn tới bội của `roundTo` gần nhất (0 = không làm tròn). Giá 0đ hợp lệ (quà tặng, đổi điểm…) —
 * chỉ cảnh báo, không chặn (Owner 2026-11-15).
 */
final readonly class BulkPriceRule
{
    public const MODES = ['percent', 'amount', 'fixed'];

    public const ROUNDINGS = [0, 1000];

    /** Cảnh báo theo dòng (không chặn lưu). */
    public const WARN_NO_BASE = 'no_base';

    public const WARN_ZERO = 'zero';

    public const WARN_NOT_LOWER = 'not_lower';

    public const WARN_OVER_CAP = 'over_cap';

    public function __construct(public string $mode, public int $value, public int $roundTo = 1000)
    {
        if (! in_array($mode, self::MODES, true) || $value < 0 || ($mode === 'percent' && $value > 10_000) || $roundTo < 0) {
            throw new InvalidArgumentException("Cách tính giá không hợp lệ: {$mode} {$value}");
        }
    }

    /**
     * Giá mới; null khi cần giá niêm yết mà SKU chưa có.
     */
    public function apply(?int $listAmount): ?int
    {
        $amount = match ($this->mode) {
            'fixed' => $this->value,
            'percent' => $listAmount === null ? null : intdiv($listAmount * (10_000 - $this->value), 10_000),
            'amount' => $listAmount === null ? null : $listAmount - $this->value,
        };
        if ($amount === null) {
            return null;
        }
        if ($this->roundTo > 0 && $this->mode !== 'fixed') {
            $amount = (int) (round($amount / $this->roundTo) * $this->roundTo);
        }

        return max(0, $amount);
    }

    /**
     * Cảnh báo cho một giá so với giá niêm yết. `$capBasisPoints` = mức giảm tối đa cửa hàng cấu hình cho khuyến mãi
     * (vanishop.promotion.max_discount_bp); 10000 = không giới hạn.
     *
     * @return list<string>
     */
    public static function warnings(?int $listAmount, ?int $amount, int $capBasisPoints): array
    {
        if ($amount === null) {
            return [self::WARN_NO_BASE];
        }
        $warnings = [];
        if ($amount === 0) {
            $warnings[] = self::WARN_ZERO;
        }
        if ($listAmount !== null && $amount >= $listAmount) {
            $warnings[] = self::WARN_NOT_LOWER;
        }
        if ($listAmount !== null && $listAmount > 0 && $capBasisPoints < 10_000 && ($listAmount - $amount) * 10_000 > $capBasisPoints * $listAmount) {
            $warnings[] = self::WARN_OVER_CAP;
        }

        return $warnings;
    }

    /**
     * Phần trăm giảm so với giá niêm yết, làm tròn xuống (không phóng đại mức giảm); null khi không giảm/không có niêm yết.
     */
    public static function discountPercent(?int $listAmount, ?int $amount): ?int
    {
        if ($listAmount === null || $amount === null || $listAmount <= 0 || $amount >= $listAmount) {
            return null;
        }

        return intdiv(($listAmount - $amount) * 100, $listAmount);
    }
}
