<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

final readonly class IncomingStockData
{
    public function __construct(
        public int $variantId,
        public int $quantity,
        public ?string $expectedOn,
        public string $source,
    ) {}
}
