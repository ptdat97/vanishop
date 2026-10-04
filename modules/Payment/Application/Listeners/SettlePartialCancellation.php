<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Payment\Application\PaymentService;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class SettlePartialCancellation
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly CurrentContext $context,
    ) {}

    public function handle(OrderLinesCancelled $event): void
    {
        $this->context->runAs(ContextScope::system('partial cancellation'), fn () => $this->payments->settlePartialCancellation($event->orderId, $event->amount, $event->cancellationId, $event->reason));
    }
}
