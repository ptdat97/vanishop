<?php

declare(strict_types=1);

namespace Modules\Catalog\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Domain event công khai — dispatch sau commit (Pricing, Inventory, Integration có thể lắng nghe).
 */
final readonly class VariantCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $variantId,
        public int $styleId,
        public int $brandId,
        public string $sku,
    ) {}
}
