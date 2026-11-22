<?php

declare(strict_types=1);

namespace Plugin\Bundles\Domain;

/**
 * Cấu hình rule/action của khuyến mãi combo, lưu trong khuyến mãi của Core: `items` = {style id: số lượng mỗi bộ}
 * (từ 2 đến MAX_ITEMS món), action thêm `basis_points` (1%–90%). Đánh giá giỏ không cần đọc bảng của plugin.
 */
final class BundleConfig
{
    public const MIN_ITEMS = 2;

    public const MAX_ITEMS = 6;

    public const MAX_QUANTITY = 10;

    public const MIN_BASIS_POINTS = 100;

    public const MAX_BASIS_POINTS = 9_000;

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, int>|null style id => số lượng; null = không hợp lệ
     */
    public static function requirement(array $config): ?array
    {
        $items = $config['items'] ?? null;
        if (! is_array($items) || count($items) < self::MIN_ITEMS || count($items) > self::MAX_ITEMS) {
            return null;
        }
        $requirement = [];
        foreach ($items as $styleId => $quantity) {
            if (! is_numeric($styleId) || (int) $styleId < 1 || ! is_int($quantity) || $quantity < 1 || $quantity > self::MAX_QUANTITY) {
                return null;
            }
            $requirement[(int) $styleId] = $quantity;
        }

        return $requirement;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function basisPoints(array $config): ?int
    {
        $basisPoints = $config['basis_points'] ?? null;

        return is_int($basisPoints) && $basisPoints >= self::MIN_BASIS_POINTS && $basisPoints <= self::MAX_BASIS_POINTS ? $basisPoints : null;
    }
}
