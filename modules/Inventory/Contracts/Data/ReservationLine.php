<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

final readonly class ReservationLine
{
    public function __construct(
        public int $variantId,
        public int $quantity,
    ) {}
}
