<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;

/**
 * Nguồn phương thức giao + phí cho checkout (tag `vani.checkout.shipping_providers`).
 * Bắt buộc ≥ 1 đang bật (ADR-029). Phí cố định là plugin hệ thống `vani.shipping-flat-rate`; carrier (GHN…) là plugin.
 * Không gọi mạng đồng bộ không có timeout/cache.
 */
interface ShippingRateProvider
{
    public const TAG = 'vani.checkout.shipping_providers';

    /**
     * @return list<ShippingOption>
     */
    public function options(TotalsContext $context): array;
}
