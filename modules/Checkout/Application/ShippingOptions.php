<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Illuminate\Contracts\Container\Container;
use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Extension\Facades\Hook;

final class ShippingOptions
{
    public const TAG = 'vani.checkout.shipping_providers';

    public function __construct(private readonly Container $container) {}

    /**
     * @return list<ShippingOption>
     */
    public function for(TotalsContext $context): array
    {
        $options = [];
        /** @var ShippingRateProvider $provider */
        foreach ($this->container->tagged(self::TAG) as $provider) {
            array_push($options, ...$provider->options($context));
        }

        $filtered = Hook::filter('vani.checkout.shipping_options', $options, $context);

        return array_values(array_filter(is_array($filtered) ? $filtered : $options, fn (mixed $option): bool => $option instanceof ShippingOption));
    }
}
