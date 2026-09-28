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
}
