<?php

declare(strict_types=1);

namespace Modules\Returns\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ReturnRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $returnId, public int $orderId, public string $number, public string $source, public ?int $brandId = null) {}
}
