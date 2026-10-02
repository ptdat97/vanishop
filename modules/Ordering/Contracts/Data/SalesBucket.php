<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

/**
 * Một nhóm số liệu: `key` (ngày Y-m-d, mã phương thức, SKU…), `label` hiển thị, số đơn, số lượng (theo dòng), doanh thu.
 */
final readonly class SalesBucket
{
    public function __construct(
        public string $key,
        public string $label,
        public int $ordersCount,
        public int $quantity,
        public int $revenue,
    ) {}
}
