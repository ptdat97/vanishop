<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class CancelShipmentsOnOrderCancel
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
        private readonly CurrentContext $context,
    ) {}

    public function handle(OrderCancelled $event): void
    {
        $this->context->runAs(ContextScope::system('cancel shipments'), fn () => $this->fulfillment->cancelOpenShipments($event->orderId, $event->reason));
    }
}
