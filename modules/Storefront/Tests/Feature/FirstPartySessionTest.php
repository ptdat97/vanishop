<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Shared\Support\Phones;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| ADR-044 §4: Storefront API nhận phiên web của chính website cho request cùng nguồn — JS của theme gọi API bằng phiên đăng
| nhập của trang, không giữ token trong JavaScript. Request khác nguồn hoặc không có dấu hiệu cùng nguồn không dùng phiên.
*/

beforeEach(function () {
    H::fakeOtp();
    C::store();
    $this->post('/tai-khoan/dang-nhap/otp', ['phone' => '0912345678']);
    $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString('0912345678')->e164.'|login']])->assertRedirect();
});

it('khách đăng nhập trên Blade: API cùng nguồn nhận ra khách', function (array $headers) {
    $this->getJson('/api/storefront/v1/me', $headers)->assertOk()->assertJsonPath('data.phone', '+84912345678');
})->with([
    'Sec-Fetch-Site: same-origin' => [['Sec-Fetch-Site' => 'same-origin']],
    'Origin trùng host (trình duyệt cũ)' => [['Origin' => 'http://vanishop.test']],
]);

it('khác nguồn, hoặc không có dấu hiệu cùng nguồn: không dùng phiên web — phải có Bearer', function (array $headers) {
    $this->getJson('/api/storefront/v1/me', $headers)->assertUnauthorized();
})->with([
    'Sec-Fetch-Site: cross-site' => [['Sec-Fetch-Site' => 'cross-site', 'Origin' => 'http://vanishop.test']],
    'Origin khác host' => [['Origin' => 'https://ke-gian.example']],
    'không có header' => [[]],
]);

it('đăng xuất trên Blade: API cùng nguồn coi là khách vãng lai, route optional vẫn chạy', function () {
    $this->post('/tai-khoan/dang-xuat')->assertRedirect();

    $this->getJson('/api/storefront/v1/me', ['Sec-Fetch-Site' => 'same-origin'])->assertUnauthorized();
    $this->postJson('/api/storefront/v1/carts', [], ['Sec-Fetch-Site' => 'same-origin'])->assertCreated();
});

/** Laravel tắt kiểm CSRF khi chạy test — bật lại để kiểm đúng hành vi production (stabilization R5). */
function enforceCsrf(): void
{
    app()->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });
}

it('ghi bằng phiên cùng nguồn: Sec-Fetch-Site same-origin là bằng chứng chống CSRF; chỉ có Origin (trình duyệt cũ) thì phải kèm token', function () {
    enforceCsrf();
    $address = fn (string $label): array => ['label' => $label, 'full_name' => 'Lan', 'phone' => '0912345678', 'province_code' => '29', 'province_name' => 'Thành phố Hồ Chí Minh',
        'ward_code' => '70101065', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi'];
    $oldBrowser = ['Origin' => 'http://vanishop.test'];

    // Trình duyệt hiện đại tự đặt Sec-Fetch-Site; trang khác nguồn không giả được header này (Laravel kiểm theo nó).
    $this->postJson('/api/storefront/v1/me/addresses', $address('Nhà'), ['Sec-Fetch-Site' => 'same-origin'])->assertCreated();

    $this->postJson('/api/storefront/v1/me/addresses', $address('Cty'), $oldBrowser)->assertStatus(419);
    $this->postJson('/api/storefront/v1/me/addresses', $address('Cty'), [...$oldBrowser, 'X-CSRF-TOKEN' => 'sai'])->assertStatus(419);
    $this->postJson('/api/storefront/v1/me/addresses', $address('Cty'), [...$oldBrowser, 'X-CSRF-TOKEN' => app('session.store')->token()])->assertCreated();

    $styleId = (int) DB::table('styles')->value('id');
    $this->postJson('/api/storefront/v1/products/views', ['style_id' => $styleId], $oldBrowser)->assertNoContent();
});

it('có Authorization thì không dùng phiên web: token sai → 401 dù trình duyệt đang đăng nhập', function () {
    $this->getJson('/api/storefront/v1/me', ['Sec-Fetch-Site' => 'same-origin', 'Authorization' => 'Bearer sai'])->assertUnauthorized();
});

it('khác nguồn mà mang cookie phiên: không mở phiên, không cần CSRF nhưng cũng không là khách đã đăng nhập', function () {
    enforceCsrf();
    $this->postJson('/api/storefront/v1/me/addresses', ['full_name' => 'X'], ['Sec-Fetch-Site' => 'cross-site'])->assertUnauthorized();
    $this->postJson('/api/storefront/v1/carts', [], ['Sec-Fetch-Site' => 'same-site'])->assertCreated()->assertJsonMissingPath('data.customer');
});
