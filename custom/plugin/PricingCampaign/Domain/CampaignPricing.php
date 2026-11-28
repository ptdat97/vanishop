<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Domain;

/**
 * Giá chiến dịch từ giá niêm yết: đồng giá, giảm %, giảm số tiền; làm tròn lên tới 1.000đ (không giảm quá mức đã đặt). Giá
 * không thấp hơn giá niêm yết → null (variant không vào bảng giá).
 */
final class CampaignPricing
{
    public const ROUND_TO = 1_000;

    public static function price(string $strategy, int $value, int $listPrice): ?int
    {
        $price = match ($strategy) {
            'fixed_price' => $value,
            'percent' => $listPrice - intdiv($listPrice * max(0, min(100, $value)), 100),
            'amount' => $listPrice - $value,
            default => $listPrice,
        };
        $price = (int) (ceil(max(0, $price) / self::ROUND_TO) * self::ROUND_TO);

        return $price < $listPrice ? $price : null;
    }
}
