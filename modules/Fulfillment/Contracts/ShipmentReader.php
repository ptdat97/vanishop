<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Fulfillment\Contracts\Data\ShipmentView;

interface ShipmentReader
{
    /**
     * @return list<ShipmentView>
     */
    public function forOrder(int $orderId): array;
}
