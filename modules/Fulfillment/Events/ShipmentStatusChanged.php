<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ShipmentStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $shipmentId,
        public int $orderId,
        public string $from,
        public string $to,
        public int $codAmount,
        /** Brand của đơn — để plugin nghe event theo phạm vi brand (`PluginServiceProvider::onEvent`). */
        public ?int $brandId = null,
    ) {}
}
