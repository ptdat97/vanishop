<?php

use Illuminate\Support\Facades\Event;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\StorefrontApiContract as Api;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\PhoneOtpLogin\PhoneOtpLoginServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../../../modules/Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| vani.phone-otp-login: đăng nhập bằng mã OTP qua SĐT là phương thức bổ sung (Core mặc định email + mật khẩu, 0.5.12). Core
| giữ khung OTP + quy tắc tài khoản (contract PhoneOtpLogin); plugin thêm form ở trang đăng nhập và API x/vani-phone-otp-login.
*/

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(PhoneOtpLoginServiceProvider::ID);
        app(PluginManager::class)->enable(PhoneOtpLoginServiceProvider::ID);
    });
    app()->register(PhoneOtpLoginServiceProvider::class);
    $this->api = '/api/storefront/v1/x/vani-phone-otp-login';
    $this->plugin = dirname(__DIR__, 2);
});

it('API được mô tả trong openapi.json của plugin', function () {
    Api::assertRoutesDocumented($this->plugin);
});

it('API: gửi mã → đổi mã lấy token; SĐT chưa có hồ sơ → tài khoản mới (không email); mã dùng một lần', function () {
    Event::fake([CustomerRegistered::class]);
    Api::assertResponse($this->postJson("{$this->api}/otp/request", ['phone' => '0912 345 678']), $this->plugin, '/otp/request', 'POST')
        ->assertStatus(202)->assertJsonPath('data.channel', 'sms')->assertJsonPath('data.expires_in', 300);

    $code = FakeOtpSender::$codes['+84912345678|login'];
    $token = Api::assertResponse($this->postJson("{$this->api}/otp/verify", ['phone' => '+84912345678', 'code' => $code]), $this->plugin, '/otp/verify', 'POST')
        ->assertOk()->assertJsonPath('data.phone', '+84912345678')->assertJsonPath('data.email', null)->assertJsonPath('data.registered', true)
        ->assertJsonPath('meta.token_type', 'Bearer')->json('meta.token');
    Event::assertDispatched(CustomerRegistered::class, fn (CustomerRegistered $event): bool => ! $event->claimedGuestProfile);
    $this->getJson(H::API.'/me', H::auth($token))->assertOk()->assertJsonPath('data.phone', '+84912345678');

    $this->postJson("{$this->api}/otp/verify", ['phone' => '0912345678', 'code' => $code])->assertStatus(422)->assertJsonPath('error.code', 'customer.otp_invalid');
});

it('nhập sai 5 lần thì mã bị huỷ (chống brute-force)', function () {
    $this->postJson("{$this->api}/otp/request", ['phone' => '0912345678'])->assertStatus(202);
    $code = FakeOtpSender::$codes['+84912345678|login'];
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $ignored) {
        $this->postJson("{$this->api}/otp/verify", ['phone' => '0912345678', 'code' => $wrong])->assertStatus(422);
    }
    $this->postJson("{$this->api}/otp/verify", ['phone' => '0912345678', 'code' => $code])->assertStatus(422);
});

it('giới hạn yêu cầu mã theo SĐT; không có kênh gửi → otp_unavailable; SĐT sai → phone_invalid', function () {
    foreach (range(1, 3) as $ignored) {
        $this->postJson("{$this->api}/otp/request", ['phone' => '0912345678'])->assertStatus(202);
    }
    $this->postJson("{$this->api}/otp/request", ['phone' => '0912345678'])->assertStatus(429)->assertJsonPath('error.code', 'customer.otp_rate_limited');

    FakeOtpSender::$available = false;
    $this->postJson("{$this->api}/otp/request", ['phone' => '0987654321'])->assertStatus(422)->assertJsonPath('error.code', 'customer.otp_unavailable');
    $this->postJson("{$this->api}/otp/request", ['phone' => '123'])->assertStatus(422)->assertJsonPath('error.code', 'customer.phone_invalid');
});

it('khách vãng lai đặt đơn → profile ẩn theo SĐT; đăng nhập OTP cùng SĐT → nhận lại lịch sử đơn', function () {
    Event::fake([CustomerRegistered::class]);
    $number = H::guestOrder($this, $this->s->id, 'otp-guest-0001');
    $hidden = Customer::query()->sole();

    $this->postJson("{$this->api}/otp/request", ['phone' => '0912345678'])->assertStatus(202);
    $token = $this->postJson("{$this->api}/otp/verify", ['phone' => '0912345678', 'code' => FakeOtpSender::$codes['+84912345678|login']])->assertOk()->json('meta.token');

    expect(Customer::query()->count())->toBe(1);
    Event::assertDispatched(CustomerRegistered::class, fn (CustomerRegistered $event): bool => $event->claimedGuestProfile && $event->customerId === $hidden->id);
    $this->getJson(H::API.'/me/orders', H::auth($token))->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.number', $number);
});

it('trang đăng nhập có form OTP bên cạnh email: gửi mã → ô nhập mã; sai mã → lỗi dưới form OTP; đúng → đăng nhập, gộp giỏ', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->get('/tai-khoan/dang-nhap')->assertOk()->assertSee('name="email"', false)->assertSee('data-vani-phone-otp-login', false)
        ->assertSee('Gửi mã xác thực')->assertDontSee('name="code"', false);

    $this->post('/p/vani-phone-otp-login/otp', ['phone' => '123'])->assertRedirect('/tai-khoan/dang-nhap')->assertSessionHasErrorsIn('otp', 'phone');
    $this->post('/p/vani-phone-otp-login/otp', ['phone' => '0912345678'])->assertRedirect('/tai-khoan/dang-nhap')
        ->assertSessionHas('status', 'Đã gửi mã xác thực. Vui lòng kiểm tra tin nhắn.');
    $this->get('/tai-khoan/dang-nhap')->assertSee('name="code"', false)->assertSee('Mã xác thực gửi tới 0912345678');

    $this->post('/p/vani-phone-otp-login/verify', ['code' => '000000'])->assertRedirect('/tai-khoan/dang-nhap')
        ->assertSessionHasErrorsIn('otp', ['code' => 'Mã xác thực không đúng hoặc đã hết hạn.']);
    $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');

    $this->post('/p/vani-phone-otp-login/verify', ['code' => FakeOtpSender::$codes['+84912345678|login']])->assertRedirect('/tai-khoan');
    $this->get('/tai-khoan')->assertOk()->assertSee('+84912345678');
    $this->get('/gio-hang')->assertSee('300.000 ₫');
});
