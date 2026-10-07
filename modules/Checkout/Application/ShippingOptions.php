<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;

final class ShippingOptions
{
    public const TAG = ShippingRateProvider::TAG;

    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @return list<ShippingOption>
     */
    public function for(TotalsContext $context): array
    {
        $options = [];
        foreach ($this->extensions->tagged(self::TAG) as $provider) {
            if ($provider instanceof ShippingRateProvider && $this->extensions->acceptsNewTransactions($provider)) {
                // Hãng báo cước lỗi → bỏ các lựa chọn của hãng đó, các hãng khác vẫn hiện.
                array_push($options, ...$this->extensions->call($provider, fn (): array => $provider->options($context), [], 'checkout.shipping_options'));
            }
        }

        $filtered = Hook::filter('vani.checkout.shipping_options', $options, $context);

        return array_values(array_filter(is_array($filtered) ? $filtered : $options, fn (mixed $option): bool => $option instanceof ShippingOption));
    }
}
