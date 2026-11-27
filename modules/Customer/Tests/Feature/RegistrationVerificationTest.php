<?php

use Illuminate\Support\Facades\Event;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/CustomerTestHelpers.php';

/*
 * Cài đặt `customer.registration_verification` (0.5.114): none = tạo tài khoản ngay khi điền đủ thông tin, email = link,
 * otp = mã tới SĐT (plugin). Email/SĐT trùng hồ sơ khách vãng lai đã đặt hàng luôn phải xác minh trước khi nhận lịch sử đơn.
 */
beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->mode = fn (string $mode) => app(Settings::class)->set('core', AuthService::VERIFICATION_SETTING, $mode);
    $this->register = fn (string $email = 'lan@example.com', string $phone = '0987000001') => $this->postJson(H::API.'/auth/register', [
        'full_name' => 'Nguyễn Thị Lan', 'email' => $email, 'phone' => $phone, 'password' => H::PASSWORD, 'password_confirmation' => H::PASSWORD,
    ], H::CHANNEL);
});

it('không xác minh: điền đủ thông tin → 201, tài khoản tạo ngay và đăng nhập; email chưa xác minh, không gửi link', function () {
    Event::fake([CustomerRegistered::class]);
    ($this->mode)(AuthService::VERIFY_NONE);

    $response = ($this->register)()->assertCreated()->assertJsonPath('meta.status', 'registered')->assertJsonPath('data.phone', '+84987000001');

    $customer = Customer::query()->sole();
    expect($customer->registered_at)->not->toBeNull()
        ->and($customer->email_verified_at)->toBeNull()
        ->and($customer->phone_verified_at)->toBeNull();
    $this->getJson(H::API.'/me', H::auth($response->json('meta.token')))->assertOk()->assertJsonPath('data.email', 'lan@example.com');
    Event::assertDispatched(CustomerRegistered::class, fn (CustomerRegistered $event): bool => ! $event->claimedGuestProfile);
});

it('không xác minh nhưng email trùng khách vãng lai đã đặt hàng → vẫn phải bấm link email mới nhận lịch sử đơn', function () {
    H::guestOrder($this, $this->s->id, 'reg-none-guest-1', ['phone' => '0912345678', 'email' => 'lan@example.com']);
    ($this->mode)(AuthService::VERIFY_NONE);

    ($this->register)()->assertStatus(202)->assertJsonPath('data.status', 'verify_email');
    expect(Customer::query()->whereNotNull('registered_at')->count())->toBe(0);

    $this->postJson(H::API.'/auth/register/verify', ['token' => H::mailedToken('lan@example.com')], H::CHANNEL)->assertOk();
    $account = Customer::query()->whereNotNull('registered_at')->sole();
    expect(Order::query()->withoutGlobalScopes()->sole()->customer_id)->toBe($account->id)
        ->and($account->phone)->toBe('+84912345678'); // giữ SĐT đặt hàng của hồ sơ
});

it('không xác minh, chỉ SĐT trùng khách vãng lai → mã OTP tới SĐT; đúng mã thì nhận hồ sơ, SĐT đã xác minh', function () {
    H::fakeOtp();
    H::guestOrder($this, $this->s->id, 'reg-none-guest-2', ['phone' => '0987000001', 'email' => 'khac@example.com']);
    ($this->mode)(AuthService::VERIFY_NONE);

    ($this->register)()->assertStatus(202)->assertJsonPath('data.status', 'verify_otp')->assertJsonPath('data.channel', 'sms');
    $this->postJson(H::API.'/auth/register/verify-otp', ['phone' => '0987000001', 'code' => '000000'], H::CHANNEL)->assertStatus(422)->assertJsonPath('error.code', 'customer.otp_invalid');
    $this->postJson(H::API.'/auth/register/verify-otp', ['phone' => '0987000001', 'code' => FakeOtpSender::$codes['+84987000001|register']], H::CHANNEL)->assertOk();

    $account = Customer::query()->whereNotNull('registered_at')->sole();
    expect($account->phone_verified_at)->not->toBeNull()
        ->and(Order::query()->withoutGlobalScopes()->sole()->customer_id)->toBe($account->id);
});

it('OTP: gửi mã tới SĐT, đúng mã → tài khoản; không có kênh tới SĐT thì dùng link email; SĐT đã có tài khoản → 409', function () {
    H::fakeOtp();
    ($this->mode)(AuthService::VERIFY_OTP);

    ($this->register)()->assertStatus(202)->assertJsonPath('data.status', 'verify_otp');
    expect(Customer::query()->count())->toBe(0);
    $this->postJson(H::API.'/auth/register/verify-otp', ['phone' => '0987000001', 'code' => FakeOtpSender::$codes['+84987000001|register']], H::CHANNEL)
        ->assertOk()->assertJsonPath('data.phone', '+84987000001');

    ($this->register)('mai@example.com')->assertStatus(409)->assertJsonPath('error.code', 'customer.phone_taken');

    FakeOtpSender::$available = false; // chỉ còn mã qua email — không chứng minh SĐT
    ($this->register)('mai@example.com', '0987000002')->assertStatus(202)->assertJsonPath('data.status', 'verify_email');
});

it('form đăng ký của Blade: tự chấp nhận thì đăng nhập ngay; OTP thì sang trang nhập mã', function () {
    ($this->mode)(AuthService::VERIFY_NONE);
    $form = ['full_name' => 'Lan', 'email' => 'lan@example.com', 'phone' => '0987000001', 'password' => H::PASSWORD, 'password_confirmation' => H::PASSWORD];
    $this->post('/tai-khoan/dang-ky', $form)->assertRedirect()->assertSessionHas('status');
    $this->get('/tai-khoan')->assertOk();
    $this->post('/tai-khoan/dang-xuat');

    H::fakeOtp();
    ($this->mode)(AuthService::VERIFY_OTP);
    $this->post('/tai-khoan/dang-ky', [...$form, 'email' => 'mai@example.com', 'phone' => '0987000002'])->assertRedirect('/tai-khoan/dang-ky/nhap-ma');
    $this->get('/tai-khoan/dang-ky/nhap-ma')->assertOk()->assertSee('+84987000002');
    $this->post('/tai-khoan/dang-ky/nhap-ma', ['code' => FakeOtpSender::$codes['+84987000002|register']])->assertRedirect()->assertSessionHas('status');
    expect(Customer::query()->where('email', 'mai@example.com')->sole()->phone_verified_at)->not->toBeNull();
});
