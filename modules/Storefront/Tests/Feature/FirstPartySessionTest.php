<?php

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
