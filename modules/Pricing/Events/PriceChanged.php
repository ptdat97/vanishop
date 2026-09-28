<?php

declare(strict_types=1);

namespace Modules\Pricing\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class PriceChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $variantIds
     */
    public function __construct(
        public int $priceListId,
        public int $brandId,
        public array $variantIds,
    ) {}
}
