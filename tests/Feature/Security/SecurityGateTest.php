<?php

/*
| Go-live gate (bảo mật): header bảo mật trên mọi response; vani:security:check chặn cấu hình production sai.
*/

it('header bảo mật: nosniff, chống nhúng iframe (Admin DENY), Referrer-Policy, Permissions-Policy; HSTS chỉ khi HTTPS + production', function () {
    $storefront = $this->get('/')->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    expect($storefront->headers->get('Permissions-Policy'))->toContain('camera=()')
        ->and($storefront->headers->has('Strict-Transport-Security'))->toBeFalse();

    $this->get('/admin/login')->assertHeader('X-Frame-Options', 'DENY');
    $this->getJson('/api/storefront/v1/brands')->assertHeader('X-Content-Type-Options', 'nosniff');

    app()->detectEnvironment(fn () => 'production');
    $this->get('https://vanishop.test/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('vani:security:check: cấu hình production chuẩn → qua; mỗi lỗi nghiêm trọng → mã thoát 1', function (array $bad, string $message) {
    app()->detectEnvironment(fn () => 'production');
    config([
        'app.debug' => false, 'app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.url' => 'https://shop.vn', 'session.secure' => true,
        'vanishop.admin.path' => 'quan-tri-x9', 'mail.default' => 'smtp', 'vanishop.plugins.safe_mode' => false, 'vanishop.customer.otp.log_sender' => false,
    ]);
    $this->artisan('vani:security:check')->expectsOutputToContain('Không có lỗi cấu hình')->assertSuccessful();

    config($bad);
    $this->artisan('vani:security:check')->expectsOutputToContain($message)->assertFailed();
})->with([
    'debug' => [['app.debug' => true], 'APP_DEBUG'],
    'http' => [['app.url' => 'http://shop.vn'], 'APP_URL'],
    'cookie' => [['session.secure' => null], 'SESSION_SECURE_COOKIE'],
    'admin path' => [['vanishop.admin.path' => 'admin'], 'VANI_ADMIN_PATH'],
    'mail log' => [['mail.default' => 'log'], 'MAIL_MAILER'],
    'safe mode' => [['vanishop.plugins.safe_mode' => true], 'SAFE_MODE'],
]);
