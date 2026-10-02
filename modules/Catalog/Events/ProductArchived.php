<?php

declare(strict_types=1);

namespace Modules\Catalog\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Domain event công khai — dispatch sau khi transaction commit.
 */
final readonly class ProductArchived implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $styleId,
        public string $styleCode,
    ) {}
}
