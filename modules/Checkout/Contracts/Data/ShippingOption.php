<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

final readonly class ShippingOption
{
    public function __construct(
        public string $code,
        public string $label,
        public Money $fee,
        public string $source = 'core',
    ) {}
}
