<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain;

/**
 * base: giá niêm yết; sale: giá khuyến mãi theo thời gian; member: giá thành viên (theo nhóm khách — Designed).
 */
enum PriceListType: string
{
    case Base = 'base';
    case Sale = 'sale';
    case Member = 'member';

    /** Ưu tiên mặc định khi nhân viên không nhập: giá thành viên thắng giá sale, giá sale thắng giá niêm yết. */
    public function defaultPriority(): int
    {
        return match ($this) {
            self::Base => 0,
            self::Sale => 10,
            self::Member => 20,
        };
    }
}
