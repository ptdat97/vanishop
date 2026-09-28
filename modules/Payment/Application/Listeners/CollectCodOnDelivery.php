<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Payment\Application\PaymentService;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class CollectCodOnDelivery
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly CurrentContext $context,
    ) {}

    public function handle(ShipmentStatusChanged $event): void
    {
        if ($event->to !== 'delivered' || $event->codAmount <= 0) {
            return;
        }

        $this->context->runAs(ContextScope::system('cod collected'), fn () => $this->payments->collectCod($event->orderId, $event->codAmount, "shipment:{$event->shipmentId}"));
    }
}
