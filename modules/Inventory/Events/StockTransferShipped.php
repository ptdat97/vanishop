<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Hàng đã rời kho đi (movement `transfer_out`); đang đi đường nên không bán được.
 */
final readonly class StockTransferShipped implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $variantIds
     */
    public function __construct(
        public string $publicId,
        public int $fromLocationId,
        public int $toLocationId,
        public array $variantIds,
    ) {}
}
