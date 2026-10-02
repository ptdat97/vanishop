<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

use DateTimeImmutable;

/**
 * Số đơn: <tiền tố><yymm>-<6 số>, một dãy cho cả cửa hàng, vd. VN2610-000123 (docs/09-order/order.md §5).
 */
final class OrderNumber
{
    public static function period(DateTimeImmutable $at): string
    {
        return $at->format('ym');
    }

    public static function format(string $prefix, string $period, int $sequence): string
    {
        return strtoupper($prefix).$period.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
