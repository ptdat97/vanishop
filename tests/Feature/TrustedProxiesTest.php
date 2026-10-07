<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test/client', fn () => ['ip' => request()->ip(), 'secure' => request()->isSecure()]);
});

afterEach(fn () => TrustProxies::flushState());

$forwarded = ['HTTP_X_FORWARDED_FOR' => '203.0.113.9', 'HTTP_X_FORWARDED_PROTO' => 'https'];

it('VANI_TRUSTED_PROXIES: request qua LB tin cậy lấy IP khách + HTTPS từ X-Forwarded-*', function () use ($forwarded) {
    AppServiceProvider::trustProxies('10.0.0.0/8, 192.168.1.5');

    $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'] + $forwarded)->getJson('/_test/client')
        ->assertExactJson(['ip' => '203.0.113.9', 'secure' => true]);
});

it('không cấu hình hoặc request không đến từ proxy tin cậy → bỏ qua X-Forwarded-* (chống giả IP)', function (string $setting, string $remote) use ($forwarded) {
    AppServiceProvider::trustProxies($setting);

    $this->withServerVariables(['REMOTE_ADDR' => $remote] + $forwarded)->getJson('/_test/client')
        ->assertExactJson(['ip' => $remote, 'secure' => false]);
})->with([
    'chưa cấu hình' => ['', '10.1.2.3'],
    'IP ngoài danh sách' => ['10.0.0.0/8', '198.51.100.7'],
]);
