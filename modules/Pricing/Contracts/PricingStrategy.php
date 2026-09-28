<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\ResolvedPrice;

/**
 * Extension point: cách chọn giá của variant (tag vani.pricing.strategies). Mặc định: price_list_priority.
 */
interface PricingStrategy
{
    public function code(): string;

    /**
     * @param  list<int>  $variantIds
     * @return array<int, ResolvedPrice> variant id => giá (variant không có giá thì không có mặt)
     */
    public function resolve(array $variantIds, PricingContext $context): array;
}
