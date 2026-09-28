<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class PlacedOrder
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $number,
        public int $brandId,
        public string $orderStatus,
        public string $paymentStatus,
        public int $totalAmount,
        public string $currencyCode,
    ) {}
}
