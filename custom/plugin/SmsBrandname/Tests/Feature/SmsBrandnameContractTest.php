<?php

use Illuminate\Support\Facades\Http;
use Modules\Customer\Testing\OtpSenderContract;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Testing\NotificationChannelContract;
use Plugin\SmsBrandname\Infrastructure\SmsChannel;
use Plugin\SmsBrandname\Infrastructure\SmsOtpSender;
use Plugin\SmsBrandname\SmsBrandnameServiceProvider;

beforeEach(function () {
    config(['vani.sms-brandname.api_key' => 'k', 'vani.sms-brandname.secret_key' => 's', 'vani.sms-brandname.brandname' => 'VANISHOP']);
    app()->register(SmsBrandnameServiceProvider::class);
});

NotificationChannelContract::define(
    'vani.sms-brandname',
    fn () => app(SmsChannel::class),
    fn () => new OutgoingMessage(1, 'order_placed:1:sms', 'order_placed', null, new Recipient(phone: '+84912345678'), null, 'Don LU-01 da giao', [], 1),
    new Recipient(email: 'lan@example.com'),
    succeed: fn () => Http::fake(['rest.esms.vn/*' => Http::response(['CodeResult' => '100', 'SMSID' => 'x'])]),
    failTemporarily: fn () => Http::fake(['rest.esms.vn/*' => Http::response('down', 503)]),
);

OtpSenderContract::define(
    'vani.sms-brandname',
    fn () => app(SmsOtpSender::class),
    succeed: fn () => Http::fake(['rest.esms.vn/*' => Http::response(['CodeResult' => '100', 'SMSID' => 'x'])]),
    fail: fn () => Http::fake(['rest.esms.vn/*' => Http::response(['CodeResult' => '118', 'ErrorMessage' => 'Invalid phone'])]),
);
