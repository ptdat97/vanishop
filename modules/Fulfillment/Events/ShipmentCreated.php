<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ShipmentCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $shipmentId, public int $orderId, public string $carrierCode, public ?int $brandId = null) {}
}
