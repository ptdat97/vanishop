<?php

declare(strict_types=1);

namespace Modules\Customer\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ConsentChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $customerId, public string $channel, public string $purpose, public bool $granted) {}
}
