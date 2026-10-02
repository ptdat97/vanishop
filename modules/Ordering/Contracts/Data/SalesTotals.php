<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class SalesTotals
{
    public function __construct(
        public int $ordersCount,
        public int $revenue,
        public int $discount,
        public int $shipping,
        public int $cancelledCount,
        public int $customersCount,
    ) {}

    public function averageOrderValue(): int
    {
        return $this->ordersCount === 0 ? 0 : intdiv($this->revenue, $this->ordersCount);
    }
}
