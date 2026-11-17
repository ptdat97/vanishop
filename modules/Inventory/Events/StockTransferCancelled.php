<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Shared\Contracts\PluginApi;

/**
 * Phiếu chuyển kho bị huỷ. `restocked = true` khi huỷ sau `shipped` (hàng được nhập lại kho đi).
 */
#[PluginApi]
final readonly class StockTransferCancelled implements ShouldDispatchAfterCommit
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
        public bool $restocked,
    ) {}
}
