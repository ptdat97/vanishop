<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\ResolvedPrice;

/**
 * Service contract: giá hiệu lực của variant theo kênh (Storefront, Cart, Checkout dùng).
 */
interface PriceResolver
{
    /**
     * @param  list<int>  $variantIds
     * @return array<int, ResolvedPrice>
     */
    public function forVariants(array $variantIds, PricingContext $context): array;
}
