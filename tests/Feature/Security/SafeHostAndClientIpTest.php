<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Modules\Identity\Application\RecordStaffLogin;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Http\RateLimits;

/**
 * R1-1 (reference-parity §3.1-3/4): IP client chỉ lấy qua proxy tin cậy; URL tuyệt đối chỉ theo APP_URL.
 */
afterEach(fn () => TrustProxies::flushState());

/** Header IP mà CDN/proxy hay gửi — khách tự đặt được nếu request không đi qua proxy tin cậy. */
$spoofedIpHeaders = fn (string $ip): array => [
    'HTTP_X_FORWARDED_FOR' => $ip,
    'HTTP_CF_CONNECTING_IP' => $ip,
    'HTTP_X_REAL_IP' => $ip,
    'HTTP_TRUE_CLIENT_IP' => $ip,
];

/** Origin cấu hình (APP_URL) — mọi URL tuyệt đối phải bắt đầu bằng giá trị này. */
$origin = fn (): string => rtrim((string) config('app.url'), '/');

it('Host lạ: robots.txt và sitemap.xml vẫn trỏ về APP_URL', function () use ($origin) {
    $robots = $this->get('http://evil.example/robots.txt')->assertOk()->getContent();
    $sitemap = $this->get('http://evil.example/sitemap.xml')->assertOk()->getContent();

    expect($robots)->toContain('Sitemap: '.$origin().'/sitemap.xml')->not->toContain('evil.example')
        ->and($sitemap)->toContain('<loc>'.$origin().'</loc>')->toContain('<loc>'.$origin().'/thuong-hieu</loc>')->not->toContain('evil.example');
});

it('X-Forwarded-Host từ proxy tin cậy cũng không đổi được host của URL sinh ra', function () use ($origin) {
    AppServiceProvider::trustProxies('*');

    $robots = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_HOST' => 'evil.example'])
        ->get('/robots.txt')->assertOk()->getContent();

    expect($robots)->toContain('Sitemap: '.$origin().'/sitemap.xml')->not->toContain('evil.example');
});

it('Host lạ: link trong email gửi trong request vẫn theo APP_URL', function () use ($origin) {
    Route::get('/_test/send-mail', function (): string {
        Mail::raw('Xem đơn: '.route('storefront.sitemap'), fn (Message $mail) => $mail->to('khach@example.com')->subject('Đơn hàng'));

        return 'ok';
    });

    $this->get('http://evil.example/_test/send-mail')->assertOk();

    $body = (string) Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getTextBody();
    expect($body)->toBe('Xem đơn: '.$origin().'/sitemap.xml');
});

it('ép origin + scheme theo APP_URL ngoài local; local giữ host của request để dev không vỡ', function () {
    AppServiceProvider::useAppUrlOrigin('https://shop.vn/', 'production');
    expect(url('/gio-hang'))->toBe('https://shop.vn/gio-hang');

    URL::useOrigin(null);
    URL::forceScheme(null);
    AppServiceProvider::useAppUrlOrigin('https://shop.vn', 'local');

    expect($this->get('http://vanishop.test/robots.txt')->getContent())->toContain('Sitemap: http://vanishop.test/sitemap.xml');
});

it('rate limit theo IP thật: đổi header IP giả từ IP không tin cậy không lách được giới hạn', function () use ($spoofedIpHeaders) {
    RateLimiter::for('_test-ip', fn (Request $request) => RateLimits::perMinuteByIp($request, 2));
    Route::get('/_test/limited', fn (): string => 'ok')->middleware('throttle:_test-ip');

    foreach (['203.0.113.1', '203.0.113.2'] as $fakeIp) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'] + $spoofedIpHeaders($fakeIp))->get('/_test/limited')->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'] + $spoofedIpHeaders('203.0.113.3'))->get('/_test/limited')->assertTooManyRequests();
});

it('audit ghi IP thật; khi proxy được tin (VANI_TRUSTED_PROXIES) thì lấy IP từ X-Forwarded-For, bỏ header CDN riêng', function (string $trusted, string $expectedIp) {
    AppServiceProvider::trustProxies($trusted);
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create(['password' => 'a-very-long-password']);

    $this->withServerVariables([
        'REMOTE_ADDR' => '10.1.2.3',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
        'HTTP_CF_CONNECTING_IP' => '192.0.2.44',
        'HTTP_X_REAL_IP' => '192.0.2.55',
    ])->post('/admin/login', ['email' => $staff->email, 'password' => 'a-very-long-password']);

    $login = AuditLog::query()->where('action', RecordStaffLogin::ACTION_LOGIN)->where('actor_id', $staff->id)->sole();
    expect($login->ip_address)->toBe($expectedIp);
})->with([
    'không tin proxy nào' => ['', '10.1.2.3'],
    'tin proxy 10.0.0.0/8' => ['10.0.0.0/8', '203.0.113.9'],
]);
