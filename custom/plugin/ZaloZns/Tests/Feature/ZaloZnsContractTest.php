<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Customer\Testing\OtpSenderContract;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Testing\NotificationChannelContract;
use Plugin\ZaloZns\Infrastructure\ZnsChannel;
use Plugin\ZaloZns\Infrastructure\ZnsOtpSender;
use Plugin\ZaloZns\ZaloZnsServiceProvider;

beforeEach(function () {
    config(['vani.zalo-zns.app_id' => 'app', 'vani.zalo-zns.app_secret' => 'secret', 'vani.zalo-zns.refresh_token' => 'r', 'vani.zalo-zns.otp_template_id' => 'OTP']);
    app()->register(ZaloZnsServiceProvider::class);
    Cache::put('vani.zalo-zns.access_token', 'token', 3600);
});

NotificationChannelContract::define(
    'vani.zalo-zns',
    fn () => app(ZnsChannel::class),
    fn () => new OutgoingMessage(1, 'order_placed:1:zns', 'order_placed', null, new Recipient(phone: '+84912345678'), null, null, ['template_id' => 'T1', 'params' => ['order_code' => 'LU-01']], 1),
    new Recipient(email: 'lan@example.com'),
    succeed: fn () => Http::fake(['business.openapi.zalo.me/*' => Http::response(['error' => 0, 'data' => ['msg_id' => 'm']])]),
    failTemporarily: fn () => Http::fake(['business.openapi.zalo.me/*' => Http::response('down', 503)]),
);

OtpSenderContract::define(
    'vani.zalo-zns',
    fn () => app(ZnsOtpSender::class),
    succeed: fn () => Http::fake(['business.openapi.zalo.me/*' => Http::response(['error' => 0, 'data' => ['msg_id' => 'm']])]),
    fail: fn () => Http::fake(['business.openapi.zalo.me/*' => Http::response(['error' => -118, 'message' => 'Zalo account not existed'])]),
);
