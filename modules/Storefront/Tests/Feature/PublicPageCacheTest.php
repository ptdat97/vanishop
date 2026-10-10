<?php

use Illuminate\Support\Facades\URL;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Shared\Support\Phones;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Roadmap Phase 7 (storefront §5): trang công khai chạy không phiên → HTML giống nhau với mọi khách, không Set-Cookie,
| Cache-Control public cho CDN. Phần riêng của khách qua GET /_vani/phien (không cache).
*/

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->product = '/san-pham/'.$this->s->style->slug;
    $this->signIn = function (): void {
        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => '0912345678']);
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString('0912345678')->e164.'|login']]);
    };
});

it('trang công khai: không Set-Cookie, Cache-Control public cho CDN, Vary theo locale', function (string $path) {
    $path = str_replace('{product}', $this->product, $path);
    $response = $this->get($path)->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=300, stale-while-revalidate=600')
        ->assertSee('data-vani-session=', false);

    expect($response->headers->getCookies())->toBe([])
        ->and($response->headers->get('Vary'))->toContain('X-Vani-Locale');
})->with(['trang chủ' => '/', 'sản phẩm' => '{product}', 'thương hiệu' => '/thuong-hieu', 'tìm kiếm' => '/tim-kiem?q=dam']);

it('HTML giống hệt nhau khi khách đã đăng nhập (không lộ dữ liệu riêng vào bản cache)', function () {
    $guest = $this->get($this->product)->assertOk()->getContent();
    ($this->signIn)();
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);

    expect($this->get($this->product)->assertOk()->getContent())->toBe($guest)
        ->and($guest)->toContain('Đăng nhập')->not->toContain('name="_token"');
});

it('trang riêng (giỏ, thanh toán) vẫn có phiên và không cache công khai', function () {
    $response = $this->get('/gio-hang')->assertOk();

    expect(collect($response->headers->getCookies())->map->getName()->all())->toContain(config('session.cookie'))
        ->and($response->headers->get('Cache-Control'))->not->toContain('public');
});

it('/_vani/phien: đăng nhập, số món trong giỏ; lỗi thêm giỏ từ trang cache hiện qua phiên (đọc một lần); không cache', function () {
    $this->getJson('/_vani/phien')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('signed_in', false)->assertJsonPath('account.label', 'Đăng nhập')->assertJsonPath('cart.count', 0);

    $this->from($this->product)->post('/gio-hang', ['quantity' => 1])->assertRedirect($this->product);
    $this->getJson('/_vani/phien')->assertJsonStructure(['flash' => ['errors' => ['variant_id']]]);
    $this->getJson('/_vani/phien')->assertJsonPath('flash.errors', []);

    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2])->assertRedirect();
    ($this->signIn)();
    $this->getJson('/_vani/phien')->assertJsonPath('signed_in', true)->assertJsonPath('account.label', 'Tài khoản')->assertJsonPath('cart.count', 2);
});

it('tắt bằng cấu hình hoặc link có chữ ký (xem trước) → private, no-store; vẫn không phiên', function () {
    config(['vanishop.storefront.page_cache.enabled' => false]);
    $off = $this->get('/')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    expect($off->headers->getCookies())->toBe([]);

    config(['vanishop.storefront.page_cache.enabled' => true]);
    $this->get(URL::temporarySignedRoute('storefront.product', now()->addMinutes(5), ['slug' => $this->s->style->slug]))
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/san-pham/khong-ton-tai')->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
});
