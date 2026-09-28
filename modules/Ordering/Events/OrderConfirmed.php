<?php

declare(strict_types=1);

namespace Modules\Ordering\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class OrderConfirmed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $orderId,
        public string $publicId,
        public int $brandId,
        public string $reason,
    ) {}
}
