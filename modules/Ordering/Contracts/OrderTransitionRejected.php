<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Shared\Domain\BusinessRuleViolation;

final class OrderTransitionRejected extends BusinessRuleViolation
{
    public function __construct(public readonly OrderStatus $from, public readonly OrderStatus $to)
    {
        parent::__construct(__('ordering::messages.transition_invalid', ['from' => $from->value, 'to' => $to->value]));
    }

    public function errorCode(): string
    {
        return 'order.transition_invalid';
    }

    public function details(): array
    {
        return ['from' => $this->from->value, 'to' => $this->to->value];
    }
}
