<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Hàng đã nhập kho đến (movement `transfer_in`).
 */
final readonly class StockTransferReceived implements ShouldDispatchAfterCommit
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
