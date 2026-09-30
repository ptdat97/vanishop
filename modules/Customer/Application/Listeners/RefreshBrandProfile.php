<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Listeners;

use Modules\Customer\Application\BrandProfiles;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class RefreshBrandProfile
{
    public function __construct(
        private readonly BrandProfiles $profiles,
        private readonly OrderReader $orders,
        private readonly CurrentContext $context,
    ) {}

    public function handle(OrderPlaced|OrderCancelled $event): void
    {
        $customerId = $event instanceof OrderPlaced
            ? $event->customerId
            : $this->context->runAs(ContextScope::system('customer brand profile'), fn (): ?int => $this->orders->find($event->orderId)?->customerId);

        if ($customerId !== null) {
            $this->profiles->recompute($customerId);
        }
    }
}
