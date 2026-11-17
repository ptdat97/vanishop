<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Shared\Contracts\PluginApi;

/**
 * Phiếu chuyển kho được tạo (đang `pending`, chưa đổi tồn).
 */
#[PluginApi]
final readonly class StockTransferCreated implements ShouldDispatchAfterCommit
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
