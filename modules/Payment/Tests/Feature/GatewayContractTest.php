<?php

use Illuminate\Http\Request;
use Modules\Payment\Application\Gateways\CodGateway;
use Modules\Payment\Application\Gateways\ManualBankTransferGateway;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Testing\PaymentGatewayContract;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;

PaymentGatewayContract::define('cod', fn () => new CodGateway(20_000_000));

PaymentGatewayContract::define('manual_bank_transfer', fn () => new ManualBankTransferGateway(
    ['default' => ['bank' => 'Vietcombank', 'account_number' => '0123456789', 'account_name' => 'CONG TY VANI']], 86_400,
));

PaymentGatewayContract::define(
    'fake_online (mẫu cho plugin)',
    fn () => new FakeOnlineGateway,
    validCallback: fn (PaymentData $payment) => Request::create('/callback', 'POST', FakeOnlineGateway::callbackPayload($payment->publicId, 'T1', 'paid', $payment->amount->amount)),
    tamperedCallback: fn (PaymentData $payment) => Request::create('/callback', 'POST', [...FakeOnlineGateway::callbackPayload($payment->publicId, 'T1', 'paid', $payment->amount->amount), 'amount' => '1']),
);
