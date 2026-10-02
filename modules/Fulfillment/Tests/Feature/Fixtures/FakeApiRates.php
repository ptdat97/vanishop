<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Tests\Feature\Fixtures;

use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;

/**
 * Nguồn phí giao của hãng giả `fake_api`: phương thức `fake_express`, source = mã carrier.
 */
final class FakeApiRates implements ShippingRateProvider
{
    public function options(TotalsContext $context): array
    {
        return [new ShippingOption('fake_express', 'Hãng giả — nhanh', $context->money(45_000), 'fake_api')];
    }
}
