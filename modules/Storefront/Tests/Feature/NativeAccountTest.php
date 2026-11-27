<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    $this->signIn = function (string $phone = '0912345678'): void {
        $this->post('/tai-khoan/dang-nhap', ['email' => H::account($phone)->email, 'password' => H::PASSWORD])->assertRedirect('/tai-khoan');
    };
    $this->checkoutPayload = [
        'contact' => ['full_name' => 'Lan', 'phone' => '0912345678'],
        'shipping_address' => ['province_code' => '01', 'ward_code' => '10105001', 'street_line' => '1 Tràng Tiền'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => 330_000, 'idempotency_key' => 'account-order-0001',
    ];
});

it('trang tài khoản cần đăng nhập; đăng nhập email + mật khẩu → gộp giỏ vãng lai, phiên báo Tài khoản + số món', function () {
    $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2]);

    ($this->signIn)();

    $this->get('/tai-khoan')->assertOk()->assertSee('+84912345678')->assertSee('Đăng xuất');
    $this->get('/gio-hang')->assertSee('600.000 ₫');
    // Trang chủ cache được (giống nhau với mọi khách); nhãn header lấy qua /_vani/phien.
    $this->getJson('/_vani/phien')->assertJsonPath('signed_in', true)->assertJsonPath('account.label', 'Tài khoản')->assertJsonPath('cart.count', 2);
});

it('đăng ký: gửi link xác minh (chưa tạo tài khoản), bấm link → tạo tài khoản + đăng nhập, gộp giỏ; link dùng một lần', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->get('/tai-khoan/dang-ky')->assertOk()->assertSee('name="full_name"', false);

    $this->post('/tai-khoan/dang-ky', ['full_name' => 'Lan', 'email' => 'lan@example.com', 'phone' => '0987100001', 'password' => 'mat-khau-123', 'password_confirmation' => 'khac'])
        ->assertSessionHasErrors('password');
    $this->post('/tai-khoan/dang-ky', ['full_name' => 'Lan', 'email' => 'Lan@Example.com', 'phone' => '0987100002', 'password' => 'mat-khau-123', 'password_confirmation' => 'mat-khau-123'])
        ->assertRedirect('/tai-khoan/dang-ky');
    $this->get('/tai-khoan/dang-ky')->assertSee('Kiểm tra hộp thư của bạn')->assertSee('lan@example.com');
    expect(Customer::query()->count())->toBe(0);

    $link = H::mailedLink('lan@example.com');
    expect($link)->toStartWith(url('/tai-khoan/dang-ky/xac-minh/'));
    $this->get($link)->assertRedirect('/tai-khoan');
    expect(Customer::query()->sole())->email->toBe('lan@example.com')->full_name->toBe('Lan')->registered_at->not->toBeNull()->email_verified_at->not->toBeNull();
    $this->get('/tai-khoan')->assertOk()->assertSee('Đăng xuất');
    $this->get('/gio-hang')->assertSee('300.000 ₫');

    $this->post('/tai-khoan/dang-xuat');
    $this->get($link)->assertRedirect('/tai-khoan/dang-ky')->assertSessionHasErrors(['business' => 'Link xác minh không đúng hoặc đã hết hạn. Vui lòng đăng ký lại.']);
    $this->post('/tai-khoan/dang-ky', ['full_name' => 'Lan', 'email' => 'lan@example.com', 'phone' => '0987100003', 'password' => 'mat-khau-123', 'password_confirmation' => 'mat-khau-123'])
        ->assertSessionHasErrors(['business' => 'Email đã được dùng cho tài khoản khác.']);
});

it('quên mật khẩu: không lộ email có phải tài khoản; link đặt mật khẩu mới, đăng xuất mọi phiên cũ, link dùng một lần', function () {
    $customer = H::account();
    $this->post('/tai-khoan/quen-mat-khau', ['email' => 'khong-co@example.com'])->assertRedirect('/tai-khoan/quen-mat-khau')
        ->assertSessionHas('status', 'Nếu email này đã đăng ký, chúng tôi đã gửi link đặt lại mật khẩu. Vui lòng kiểm tra hộp thư.');
    $this->post('/tai-khoan/quen-mat-khau', ['email' => strtoupper($customer->email)])
        ->assertSessionHas('status', 'Nếu email này đã đăng ký, chúng tôi đã gửi link đặt lại mật khẩu. Vui lòng kiểm tra hộp thư.');
    $oldToken = $this->postJson('/api/storefront/v1/auth/login', ['email' => $customer->email, 'password' => H::PASSWORD])->json('meta.token');

    $link = H::mailedLink($customer->email);
    expect($link)->toStartWith(url('/tai-khoan/dat-lai-mat-khau/'));
    $this->get($link)->assertOk()->assertSee('Đặt mật khẩu mới');
    $token = H::mailedToken($customer->email);
    $this->post($link, ['token' => $token, 'password' => 'mat-khau-moi-9', 'password_confirmation' => 'mat-khau-moi-9'])
        ->assertRedirect('/tai-khoan/dang-nhap')->assertSessionHas('status', 'Đã đặt mật khẩu mới. Vui lòng đăng nhập lại.');
    $this->getJson('/api/storefront/v1/me', H::auth($oldToken))->assertUnauthorized();

    $this->from($link)->post($link, ['token' => $token, 'password' => 'mat-khau-moi-0', 'password_confirmation' => 'mat-khau-moi-0'])->assertSessionHasErrors('business');
    $this->post('/tai-khoan/dang-nhap', ['email' => $customer->email, 'password' => 'mat-khau-moi-9'])->assertRedirect('/tai-khoan');
});

it('các trang đăng nhập và tài khoản có slot với dữ liệu phù hợp', function () {
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.account.login', fn () => new HtmlString('<p>PLUGIN-LOGIN</p>'));

    $this->get(route('storefront.account.login'))->assertSee('PLUGIN-LOGIN');
    ($this->signIn)();

    $hooks->onSlot('vani.storefront.account.orders', fn (array $orders) => new HtmlString('<p>PLUGIN-ORDERS:'.count($orders).'</p>'));
    $hooks->onSlot('vani.storefront.account.addresses', fn (array $addresses) => new HtmlString('<p>PLUGIN-ADDRESSES:'.count($addresses).'</p>'));
    $hooks->onSlot('vani.storefront.account.profile', fn (CustomerData $customer) => new HtmlString('<p>PLUGIN-PROFILE:'.$customer->phone.'</p>'));
    $hooks->onSlot('vani.storefront.account.menu', fn () => new HtmlString('<p>PLUGIN-MENU</p>'));

    $html = $this->get(route('storefront.account.orders'))
        ->assertSee('data-vani-account-menu-root', false)
        ->assertSee('PLUGIN-ORDERS:0')
        ->getContent();
    expect(substr_count($html, 'PLUGIN-MENU'))->toBe(1);
    $this->get(route('storefront.account.addresses'))->assertSee('PLUGIN-ADDRESSES:0');
    $this->get(route('storefront.account.profile'))->assertSee('PLUGIN-PROFILE:+84912345678');
});

it('trang đăng nhập: email + mật khẩu, link đăng ký và quên mật khẩu; sai mật khẩu → lỗi form, chưa đăng nhập', function () {
    $this->get('/tai-khoan/dang-nhap')->assertOk()->assertSee('name="email"', false)->assertSee('name="password"', false)
        ->assertSee(url('/tai-khoan/dang-ky'))->assertSee(url('/tai-khoan/quen-mat-khau'))->assertDontSee('name="phone"', false);

    $email = H::account()->email;
    $this->from('/tai-khoan/dang-nhap')->post('/tai-khoan/dang-nhap', ['email' => $email, 'password' => 'sai-mat-khau'])
        ->assertRedirect('/tai-khoan/dang-nhap')->assertSessionHasErrors(['business' => 'Email hoặc mật khẩu không đúng.']);
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
    $detail = "/tai-khoan/don-hang/{$pending->public_id}";
    $this->get($detail)->assertOk()->assertSee('Huỷ đơn')->assertSee('Tôi đặt nhầm')
        ->assertDontSee('Không liên hệ được khách');
    $this->from($detail)->post($detail.'/huy', ['reason_code' => 'unreachable'])->assertSessionHasErrors('reason_code');
    $this->post($detail.'/huy', ['reason_code' => 'changed_mind'])->assertRedirect($detail)->assertSessionHasNoErrors();
    expect($pending->fresh()->order_status->value)->toBe('cancelled');
    $event = DB::table('order_events')->where('order_id', $pending->id)->where('to_status', 'cancelled')->sole();
    expect(json_decode($event->data, true))->toMatchArray(['reason_code' => 'changed_mind']);
    expect($event->reason)->toBe('customer:Tôi đổi ý');

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
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.track.after_order', fn (array $order) => new HtmlString('<p>TRACK-ORDER:'.$order['number'].'</p>'));
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', $this->checkoutPayload);
    $order = Order::query()->withoutGlobalScopes()->sole();
    $this->flushSession();

    $this->post('/tra-cuu-don', ['number' => strtolower($order->number), 'phone' => '0912 345 678'])->assertOk()->assertSee($order->number)->assertSee('330.000 ₫')->assertSee('TRACK-ORDER:'.$order->number);
    $this->post('/tra-cuu-don', ['number' => $order->number, 'phone' => '0900000000'])->assertOk()->assertSee('Không tìm thấy đơn');
});
