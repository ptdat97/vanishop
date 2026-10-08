<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

/**
 * Nguyên nhân huỷ một phần (0.3.31) — quyết định có thu hồi khuyến mãi không (order §2.1).
 */
enum LineCancellationCause: string
{
    /** Lỗi phía shop (hết hàng, sai giá…): giữ nguyên ưu đãi cho khách. */
    case Shop = 'shop';

    /** Khách yêu cầu bớt hàng: phần ưu đãi không còn đủ điều kiện được thu hồi. */
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Shop => 'Lỗi shop (hết hàng, sai giá…)',
            self::Customer => 'Khách yêu cầu bớt hàng',
        };
    }
}
