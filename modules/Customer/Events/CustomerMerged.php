<?php

declare(strict_types=1);

namespace Modules\Customer\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CustomerMerged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $sourceId, public int $targetId, public int $movedOrders) {}
}
