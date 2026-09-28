<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Modules\Ordering\Events\OrderCancelled;
use Modules\Payment\Application\PaymentService;

final class SettleCancelledOrderPayments
{
    public function __construct(private readonly PaymentService $payments) {}

    public function handle(OrderCancelled $event): void
    {
        $this->payments->settleCancelledOrder($event->orderId, $event->reason);
    }
}
