<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Support\Phones;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    $this->signIn = function (string $phone = '0912345678'): void {
        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => $phone])->assertRedirect('/tai-khoan/dang-nhap');
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString($phone)->e164.'|login']])->assertRedirect('/tai-khoan');
    };
    $this->checkoutPayload = [
        'contact' => ['full_name' => 'Lan', 'phone' => '0912345678'],
        'shipping_address' => ['province_code' => '01', 'ward_code' => '10105001', 'street_line' => '1 Tràng Tiền'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => 330_000, 'idempotency_key' => 'account-order-0001',
    ];
});

it('trang tài khoản cần đăng nhập; đăng nhập OTP → gộp giỏ vãng lai, phiên báo Tài khoản + số món', function () {
    $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2]);

    ($this->signIn)();

    $this->get('/tai-khoan')->assertOk()->assertSee('+84912345678')->assertSee('Đăng xuất');
    $this->get('/gio-hang')->assertSee('600.000 ₫');
    // Trang chủ cache được (giống nhau với mọi khách); nhãn header lấy qua /_vani/phien.
    $this->getJson('/_vani/phien')->assertJsonPath('signed_in', true)->assertJsonPath('account.label', 'Tài khoản')->assertJsonPath('cart.count', 2);
});

it('mã OTP sai → lỗi form, chưa đăng nhập', function () {
    $this->post('/tai-khoan/dang-nhap/otp', ['phone' => '0912345678']);
    $this->post('/tai-khoan/dang-nhap', ['code' => '000000'])->assertSessionHasErrors('business');
    $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');
});

it('đặt hàng khi đã đăng nhập → đơn gắn khách, xem trong tài khoản, huỷ được; đăng xuất', function () {
    ($this->signIn)();
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', $this->checkoutPayload);
    $order = Order::query()->withoutGlobalScopes()->sole();

    expect($order->customer_id)->toBe(Customer::query()->where('phone', '+84912345678')->value('id'));
    $this->get('/tai-khoan/don-hang')->assertOk()->assertSee($order->number);

    // Đơn COD tự xác nhận và đã tạo vận đơn → khách không huỷ được nữa; đơn chờ xác nhận thì huỷ được.
    config(['vani.cod.auto_confirm' => false]);
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', [...$this->checkoutPayload, 'idempotency_key' => 'account-order-0002']);
    $pending = Order::query()->withoutGlobalScopes()->latest('id')->first();
    $this->get("/tai-khoan/don-hang/{$pending->public_id}")->assertOk()->assertSee('Huỷ đơn');
    $this->post("/tai-khoan/don-hang/{$pending->public_id}/huy", ['reason' => 'đổi ý'])->assertRedirect("/tai-khoan/don-hang/{$pending->public_id}");
    expect($pending->fresh()->order_status->value)->toBe('cancelled');

    $this->post('/tai-khoan/dang-xuat')->assertRedirect('/');
    $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');
});

it('không xem được đơn của khách khác trong tài khoản', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', [...$this->checkoutPayload, 'contact' => ['full_name' => 'Mai', 'phone' => '0988888888']]);
    $other = Order::query()->withoutGlobalScopes()->sole();
    $this->flushSession();

    ($this->signIn)();
    $this->get("/tai-khoan/don-hang/{$other->public_id}")->assertNotFound();
});

it('tra cứu đơn bằng mã + SĐT; sai thì báo không tìm thấy', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', $this->checkoutPayload);
    $order = Order::query()->withoutGlobalScopes()->sole();
    $this->flushSession();

    $this->post('/tra-cuu-don', ['number' => strtolower($order->number), 'phone' => '0912 345 678'])->assertOk()->assertSee($order->number)->assertSee('330.000 ₫');
    $this->post('/tra-cuu-don', ['number' => $order->number, 'phone' => '0900000000'])->assertOk()->assertSee('Không tìm thấy đơn');
});
