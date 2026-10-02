<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

use Modules\Inventory\Contracts\Data\ReservedLine;
use Modules\Ordering\Contracts\Data\OrderLineData;

final readonly class SourcingRequest
{
    /**
     * @param  list<OrderLineData>  $lines
     * @param  list<ReservedLine>  $reserved  hàng đang giữ theo location (từ lúc đặt)
     * @param  array<string, string>  $shippingAddress
     */
    public function __construct(
        public int $orderId,
        public array $lines,
        public array $reserved,
        public array $shippingAddress,
    ) {}
}
