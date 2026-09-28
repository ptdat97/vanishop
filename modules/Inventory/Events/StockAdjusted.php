<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class StockAdjusted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $locationId,
        public int $variantId,
        public int $onHandDelta,
        public string $reason,
    ) {}
}
