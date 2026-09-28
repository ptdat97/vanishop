<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

final readonly class ReservedLine
{
    public function __construct(
        public int $variantId,
        public int $locationId,
        public int $quantity,
    ) {}
}
