<?php

declare(strict_types=1);

namespace Modules\Ordering\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class OrderPlaced implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $orderId,
        public string $publicId,
        public string $number,
        public int $brandId,
        public int $channelId,
        public ?int $customerId,
        public int $totalAmount,
        public string $currencyCode,
    ) {}
}
