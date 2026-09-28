<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class OrderLineData
{
    public function __construct(
        public int $id,
        public int $variantId,
        public string $sku,
        public string $productName,
        public int $quantity,
    ) {}
}
