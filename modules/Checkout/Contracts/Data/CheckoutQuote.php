<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

final readonly class CheckoutQuote
{
    /**
     * @param  list<ShippingOption>  $shippingOptions
     * @param  list<array{code: string, label: string}>  $paymentMethods
     */
    public function __construct(
        public Totals $totals,
        public array $shippingOptions,
        public array $paymentMethods,
        public bool $cartReady,
    ) {}
}
