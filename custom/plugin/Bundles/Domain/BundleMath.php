<?php

declare(strict_types=1);

namespace Plugin\Bundles\Domain;

/**
 * Phép tính combo, không phụ thuộc framework.
 *
 * - Số bộ = số bộ đủ món nhỏ nhất qua các món: tổng số lượng của sản phẩm (gộp mọi size/màu) chia số lượng mỗi bộ.
 * - Chỉ phần hàng thuộc các bộ đủ món được giảm; mua dư một món thì phần dư giữ giá gốc.
 * - Một món có nhiều dòng (khác size): giảm vào dòng có đơn giá thấp nhất trước (đơn giá bằng nhau → khoá dòng nhỏ trước).
 * - Giảm mỗi dòng = đơn giá × số đơn vị được tính × % giảm, làm tròn xuống.
 */
final class BundleMath
{
    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines  khoá dòng => sản phẩm + số lượng
     * @param  array<int, int>  $requirement  product id => số lượng mỗi bộ
     */
    public static function sets(array $lines, array $requirement): int
    {
        if ($requirement === []) {
            return 0;
        }
        $owned = [];
        foreach ($lines as $line) {
            $owned[$line['product_id']] = ($owned[$line['product_id']] ?? 0) + max(0, $line['quantity']);
        }
        $sets = PHP_INT_MAX;
        foreach ($requirement as $productId => $need) {
            $sets = min($sets, intdiv($owned[$productId] ?? 0, max(1, $need)));
        }

        return $sets;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int}>  $lines  khoá dòng => dòng (đơn giá: minor unit)
     * @param  array<int, int>  $requirement
     * @return array<int, int> khoá dòng => số tiền giảm (> 0)
     */
    public static function discounts(array $lines, array $requirement, int $basisPoints): array
    {
        $sets = self::sets($lines, $requirement);
        if ($sets === 0 || $basisPoints <= 0) {
            return [];
        }

        $discounts = [];
        foreach ($requirement as $productId => $need) {
            $candidates = array_filter($lines, fn (array $line): bool => $line['product_id'] === $productId && $line['quantity'] > 0);
            uksort($candidates, fn (int $a, int $b): int => [$candidates[$a]['unit_price'], $a] <=> [$candidates[$b]['unit_price'], $b]);
            $units = $need * $sets;
            foreach ($candidates as $key => $line) {
                if ($units === 0) {
                    break;
                }
                $taken = min($units, $line['quantity']);
                $units -= $taken;
                $amount = intdiv($line['unit_price'] * $taken * $basisPoints, 10_000);
                if ($amount > 0) {
                    $discounts[$key] = $amount;
                }
            }
        }

        return $discounts;
    }
}
