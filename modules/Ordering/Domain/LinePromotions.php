<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

/**
 * Giảm giá theo từng khuyến mãi trên một dòng đơn ứng với số lượng hiện tại. `meta.promotions` (ghi lúc đặt, 0.3.31)
 * lưu số tiền ứng với `meta.promotions_basis_qty` đơn vị (mặc định số lượng lúc đặt); huỷ một phần thu nhỏ theo tỷ lệ.
 */
final class LinePromotions
{
    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<int, int> promotion id => số tiền (làm tròn xuống)
     */
    public static function current(?array $meta, int $quantity, int $cancelledQuantity): array
    {
        $amounts = (array) ($meta['promotions'] ?? []);
        $basis = (int) ($meta['promotions_basis_qty'] ?? ($quantity + $cancelledQuantity));
        if ($basis <= 0) {
            return [];
        }

        $current = [];
        foreach ($amounts as $promotionId => $amount) {
            $current[(int) $promotionId] = intdiv((int) $amount * $quantity, $basis);
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function known(?array $meta): bool
    {
        return isset($meta['promotions']) && is_array($meta['promotions']);
    }
}
