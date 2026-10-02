<?php

use Plugin\SmsBrandname\Infrastructure\SmsHealthCheck;

it('báo lỗi khi thiếu khoá eSMS, cảnh báo khi sandbox, ok khi đủ cấu hình', function () {
    config(['vani.sms-brandname.api_key' => '', 'vani.sms-brandname.secret_key' => '']);
    expect(app(SmsHealthCheck::class)->check()->status)->toBe('error');

    config(['vani.sms-brandname.api_key' => 'k', 'vani.sms-brandname.secret_key' => 's', 'vani.sms-brandname.brandname' => 'VANI', 'vani.sms-brandname.sandbox' => true]);
    expect(app(SmsHealthCheck::class)->check()->status)->toBe('warning');

    config(['vani.sms-brandname.sandbox' => false]);
    expect(app(SmsHealthCheck::class)->check()->status)->toBe('ok');
});
