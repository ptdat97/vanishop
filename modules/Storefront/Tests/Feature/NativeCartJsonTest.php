<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Shared\Support\Phones;
use Modules\Storefront\Application\CartVoucher;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Giỏ mini offcanvas + window.vani (0.3.93): cùng route web của native storefront, `Accept: application/json` trả trạng thái
| giỏ (cart, count, voucher, html của partial mini-cart, change). Form không JS vẫn chuyển hướng như cũ.
*/

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m] = C::store();
});

it('thêm / sửa số lượng / xoá dòng bằng JSON: trả số món, HTML giỏ mini và thay đổi cho sự kiện vani:cart-updated', function () {
    $added = $this->postJson('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2])->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('change.action', 'add')
        ->assertJsonPath('change.quantity', 2)
        ->assertJsonPath('message', 'Đã thêm vào giỏ hàng.')
        ->assertJsonPath('redirect', null)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->json();
    $line = $added['cart']['lines'][0]['id'];
    expect($added['html'])->toContain('data-cart-quantity data-line="'.$line.'" data-quantity="3"')->toContain('600.000 ₫');

    $this->postJson("/gio-hang/{$line}", ['quantity' => 1])->assertOk()->assertJsonPath('count', 1)->assertJsonPath('change.action', 'remove')->assertJsonPath('change.quantity', 1);
    $this->getJson('/gio-hang')->assertOk()->assertJsonPath('count', 1)->assertJsonPath('cart.lines.0.quantity', 1);

    $removed = $this->postJson("/gio-hang/{$line}/xoa")->assertOk()->assertJsonPath('count', 0)->json();
    expect($removed['html'])->toContain('Giỏ hàng trống.');

    $this->postJson('/gio-hang', [])->assertUnprocessable()->assertJsonValidationErrors(['variant_id' => 'Vui lòng chọn màu và size.']);
    // Không JS: vẫn là form chuyển hướng.
    $this->post('/gio-hang', ['variant_id' => $this->s->id])->assertRedirect('/gio-hang');
});

it('Mua ngay bằng JSON trả đường dẫn checkout', function () {
    $this->postJson('/gio-hang', ['variant_id' => $this->s->id, 'buy_now' => 1])->assertOk()->assertJsonPath('redirect', url('/thanh-toan'));
});

it('mã giảm giá ở giỏ: mã sai báo lý do; mã đúng hiện mức giảm, giữ trong phiên, checkout điền sẵn; đặt hàng xong thì quên mã', function () {
    C::promotion(['name' => 'Giảm 10%'], ['GIAM10' => null]);
    $this->postJson('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2])->assertOk();

    $this->postJson('/gio-hang/ma-giam-gia', ['code' => 'KHONGCO'])->assertUnprocessable()->assertJsonValidationErrors(['code' => 'Mã KHONGCO không tồn tại.']);
    expect(session(CartVoucher::SESSION_KEY))->toBeNull();

    $applied = $this->postJson('/gio-hang/ma-giam-gia', ['code' => 'GIAM10'])->assertOk()
        ->assertJsonPath('voucher.code', 'GIAM10')
        ->assertJsonPath('voucher.discount.amount', 60_000)
        ->assertJsonPath('voucher.total.amount', 540_000)
        ->assertJsonPath('voucher.rejection', null)
        ->assertJsonPath('message', 'Đã áp dụng mã GIAM10.')
        ->json();
    expect($applied['html'])->toContain('Mã GIAM10')->toContain('540.000 ₫');

    $this->get('/gio-hang')->assertOk()->assertSee('Mã GIAM10')->assertSee('540.000 ₫');
    $this->get('/thanh-toan')->assertOk()->assertSee('value="GIAM10"', false);

    $this->postJson('/gio-hang/ma-giam-gia', ['code' => ''])->assertOk()->assertJsonPath('voucher', null);
    $this->get('/thanh-toan')->assertOk()->assertDontSee('value="GIAM10"', false);

    // Không JS: form ở trang giỏ.
    $this->post('/gio-hang/ma-giam-gia', ['code' => 'GIAM10'])->assertRedirect('/gio-hang')->assertSessionHas('status', 'Đã áp dụng mã GIAM10.');
    $this->post('/thanh-toan', [
        'contact' => ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678', 'email' => 'lan@example.com'],
        'shipping_address' => ['province_code' => '29', 'ward_code' => '70101065', 'street_line' => '12 Lê Lợi'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'voucher_code' => 'GIAM10',
        'expected_total' => 540_000, 'idempotency_key' => 'json-cart-voucher-01',
    ])->assertSessionHasNoErrors();
    expect(session(CartVoucher::SESSION_KEY))->toBeNull();
});

it('đăng nhập OTP bằng JSON: gửi mã, xác thực trả đường dẫn quay lại và token CSRF mới', function () {
    H::fakeOtp();
    $this->postJson('/tai-khoan/dang-nhap/otp', ['phone' => '0912345678'])->assertOk()
        ->assertJsonPath('message', 'Đã gửi mã xác thực. Vui lòng kiểm tra tin nhắn.')->assertJsonPath('phone', '0912345678');

    // Lỗi nghiệp vụ trong request web JSON: mã trạng thái + câu báo lỗi, không phải trang lỗi 500.
    $this->postJson('/tai-khoan/dang-nhap', ['code' => '000000'])->assertUnprocessable()
        ->assertJsonPath('errors.business.0', 'Mã xác thực không đúng hoặc đã hết hạn.');
    $code = FakeOtpSender::$codes[Phones::fromString('0912345678')->e164.'|login'];
    $result = $this->postJson('/tai-khoan/dang-nhap', ['code' => $code])->assertOk()
        ->assertJsonPath('signed_in', true)->assertJsonPath('redirect', url('/tai-khoan'))->json();
    expect($result['csrf_token'])->toBeString()->not->toBe('');

    $this->postJson('/tai-khoan/dang-xuat')->assertOk()->assertJsonPath('signed_in', false);
});

it('layout: nút "Giỏ hàng" mở offcanvas (không icon), khung dialog, URL cho window.vani theo ngôn ngữ; danh sách có vùng thay được', function () {
    $html = $this->get('/tim-kiem')->assertOk()->getContent();

    expect($html)->toContain('data-vani-cart-open aria-haspopup="dialog">Giỏ hàng')
        ->toContain('<dialog class="vani-offcanvas" data-vani-offcanvas-cart')
        ->toContain('data-vani-listing')
        ->and(preg_match('#<script type="application/json" data-vani-urls>(.*?)</script>#s', $html, $match))->toBe(1);
    $urls = json_decode($match[1], true);
    expect($urls['cart_line'])->toBe(url('/gio-hang/987654321'))
        ->and($urls['cart_voucher'])->toBe(url('/gio-hang/ma-giam-gia'))
        ->and($urls['login_otp'])->toBe(url('/tai-khoan/dang-nhap/otp'));

    $english = json_decode(preg_match('#data-vani-urls>(.*?)</script>#s', $this->get('/en/search')->getContent(), $en) ? $en[1] : '{}', true);
    expect($english['cart_voucher'] ?? null)->toBe(url('/en/cart/voucher'));
});
