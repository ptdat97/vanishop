<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;

/**
 * Nguồn phương thức giao + phí cho checkout (tag `vani.checkout.shipping_providers`).
 * Core: `flat_rate` theo cấu hình. Carrier (GHN…) là plugin, thêm ở slice Shipment/Proof plugins.
 * Không gọi mạng đồng bộ không có timeout/cache.
 */
interface ShippingRateProvider
{
    /**
     * @return list<ShippingOption>
     */
    public function options(TotalsContext $context): array;
}
