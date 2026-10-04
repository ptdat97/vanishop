<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Listeners;

use Modules\Customer\Application\CustomerStats;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class RefreshCustomerStats
{
    public function __construct(
        private readonly CustomerStats $stats,
        private readonly OrderReader $orders,
        private readonly CurrentContext $context,
    ) {}

    public function handle(OrderPlaced|OrderCancelled|OrderLinesCancelled $event): void
    {
        $customerId = $event instanceof OrderPlaced
            ? $event->customerId
            : $this->context->runAs(ContextScope::system('customer stats'), fn (): ?int => $this->orders->find($event->orderId)?->customerId);

        if ($customerId !== null) {
            $this->stats->recompute($customerId);
        }
    }
}
