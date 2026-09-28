<?php

use Illuminate\Http\Request;
use Modules\Fulfillment\Application\Carriers\ManualCarrier;
use Modules\Fulfillment\Testing\ShippingCarrierContract;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;

ShippingCarrierContract::define('manual', fn () => new ManualCarrier);

ShippingCarrierContract::define(
    'fake_api (mẫu cho plugin)',
    fn () => new FakeApiCarrier,
    validWebhook: fn (string $tracking) => Request::create('/webhook', 'POST', FakeApiCarrier::webhook($tracking, 'in_transit', 'E1')),
    tamperedWebhook: fn (string $tracking) => Request::create('/webhook', 'POST', [...FakeApiCarrier::webhook($tracking, 'in_transit', 'E1'), 'status' => 'delivered']),
);
