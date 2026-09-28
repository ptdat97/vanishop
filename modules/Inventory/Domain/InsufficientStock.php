<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

use DomainException;

final class InsufficientStock extends DomainException
{
    public function __construct(
        public readonly int $variantId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct("Không đủ hàng cho variant #{$variantId}: cần {$requested}, còn {$available}.");
    }
}
