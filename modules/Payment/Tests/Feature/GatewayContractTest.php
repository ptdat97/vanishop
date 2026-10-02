<?php

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Testing\PaymentGatewayContract;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;

PaymentGatewayContract::define(
    'fake_online (mẫu cho plugin)',
    fn () => new FakeOnlineGateway,
    validCallback: fn (PaymentData $payment) => Request::create('/callback', 'POST', FakeOnlineGateway::callbackPayload($payment->publicId, 'T1', 'paid', $payment->amount->amount)),
    tamperedCallback: fn (PaymentData $payment) => Request::create('/callback', 'POST', [...FakeOnlineGateway::callbackPayload($payment->publicId, 'T1', 'paid', $payment->amount->amount), 'amount' => '1']),
);
