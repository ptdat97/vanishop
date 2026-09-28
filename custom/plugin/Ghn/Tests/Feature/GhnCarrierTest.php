<?php

use Illuminate\Http\Request;
use Modules\Fulfillment\Testing\ShippingCarrierContract;
use Plugin\Ghn\Infrastructure\GhnCarrier;

$carrier = fn () => new GhnCarrier(
    token: 'test-token',
    shopId: '123456',
    webhookSecret: 'test-ghn-secret',
);

$makeWebhook = function (string $tracking, string $status = 'delivering', bool $tamper = false): Request {
    $fields = [
        'OrderCode' => $tracking,
        'Status' => $status,
        'Token' => $tamper ? 'invalid-token' : 'test-ghn-secret',
        'EventId' => "evt-{$tracking}-1",
    ];

    return Request::create('/api/shipping/ghn/webhook', 'POST', $fields);
};

ShippingCarrierContract::define(
    'vani.ghn',
    $carrier,
    validWebhook: fn (string $tracking) => $makeWebhook($tracking, 'delivering'),
    tamperedWebhook: fn (string $tracking) => $makeWebhook($tracking, 'delivering', tamper: true),
);
