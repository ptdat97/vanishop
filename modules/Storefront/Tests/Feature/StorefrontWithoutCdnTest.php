<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Shared\Support\Phones;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Chế độ mặc định — chưa có CDN (`vanishop.storefront.page_cache.enabled` = false, storefront §5): trang công khai chạy
| có phiên như mọi trang, phần riêng của khách render sẵn; hiển thị giống chế độ CDN.
*/

/**
 * Trạng thái khách nhúng trong layout (`script[data-vani-session-state]`).
 *
 * @return array<string, mixed>|null
 */
function inlineSessionState(string $html): ?array
{
    return preg_match('#<script type="application/json" data-vani-session-state>(.*?)</script>#s', $html, $match) === 1 ? json_decode($match[1], true) : null;
}

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->product = '/san-pham/'.$this->s->style->slug;
    $this->signIn = function (): void {
        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => '0912345678']);
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString('0912345678')->e164.'|login']]);
    };
});

it('mặc định tắt cache CDN: trang công khai có phiên, private no-store, không gọi /_vani/phien; SEO vẫn đầy đủ', function () {
    expect(config('vanishop.storefront.page_cache.enabled'))->toBeFalse();

    $response = $this->get($this->product)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    expect(collect($response->headers->getCookies())->map->getName()->all())->toContain(config('session.cookie'))
        ->and($response->getContent())->not->toContain('data-vani-session=')
        ->toContain('<link rel="canonical" href="'.url($this->product).'">')
        ->toContain('hreflang="vi"')
        ->not->toContain('name="robots" content="noindex"');
});

it('khách đã đăng nhập + có giỏ: nhãn tài khoản, số món, giỏ mini render sẵn; dữ liệu giống hệt /_vani/phien', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2])->assertRedirect();
    ($this->signIn)();

    $html = $this->get('/')->assertOk()
        ->assertSee('data-vani-account>Tài khoản</a>', false)
        ->assertSee('data-vani-cart-count  class="rounded-full bg-primary px-1.5 text-xs text-white">2</span>', false)
        ->getContent();
    $state = inlineSessionState($html);

    expect($state['signed_in'])->toBeTrue()->and($state['cart']['count'])->toBe(2)->and($state['cart']['lines'])->toHaveCount(1);

    $session = $this->getJson('/_vani/phien')->json();
    expect($state)->toEqual(collect($session)->only(array_keys($state))->all());
});

it('lỗi và thông báo của lần gửi trước render sẵn (không cần JS)', function () {
    $this->from($this->product)->post('/gio-hang', [])->assertRedirect($this->product);
    $this->get($this->product)->assertOk()->assertSee(__('storefront::messages.choose_variant'));

    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 999]);
    $this->get('/gio-hang')->assertOk()->assertSee('role="alert"', false);
});

it('cửa hàng ẩn giá với khách chưa đăng nhập: khách đã đăng nhập thấy giá ngay trong HTML', function () {
    T::seed(fn () => app(Settings::class)->set('core', 'storefront.price_visibility', 'members'));
    $this->get($this->product)->assertSee('Đăng nhập để xem giá')->assertDontSee('300.000');

    ($this->signIn)();
    $this->get($this->product)->assertDontSee('Đăng nhập để xem giá')->assertSee('300.000');
});

it('thêm giỏ từ trang tiếng Anh (không token CSRF trong form) chạy ở cả hai chế độ', function (bool $cdn) {
    config(['vanishop.storefront.page_cache.enabled' => $cdn]);

    $this->post('/en/cart', ['variant_id' => $this->s->id, 'quantity' => 1])->assertRedirect(url('/en/cart'));
    $this->getJson('/_vani/phien')->assertJsonPath('cart.count', 1);
})->with(['không CDN' => false, 'CDN' => true]);
