<?php

use Illuminate\Support\Facades\Event;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;

require_once __DIR__.'/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
});

it('OTP: yêu cầu → xác thực → tài khoản mới + token; /me; đăng xuất thu hồi token', function () {
    Event::fake([CustomerRegistered::class]);
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912 345 678'], H::CHANNEL)->assertStatus(202)
        ->assertJsonPath('data.channel', 'sms')->assertJsonPath('data.expires_in', 300);

    $response = $this->postJson(H::API.'/auth/otp/verify', ['phone' => '+84912345678', 'code' => FakeOtpSender::$codes['+84912345678|login']], H::CHANNEL)->assertOk()
        ->assertJsonPath('data.phone', '+84912345678')->assertJsonPath('data.registered', true)->assertJsonPath('meta.token_type', 'Bearer');
    $token = $response->json('meta.token');

    $this->getJson(H::API.'/me', H::auth($token))->assertOk()->assertJsonPath('data.id', $response->json('data.id'));
    Event::assertDispatched(CustomerRegistered::class, fn (CustomerRegistered $event): bool => ! $event->claimedGuestProfile);

    // Mã đã dùng không dùng lại được.
    $this->postJson(H::API.'/auth/otp/verify', ['phone' => '0912345678', 'code' => FakeOtpSender::$codes['+84912345678|login']], H::CHANNEL)
        ->assertStatus(422)->assertJsonPath('error.code', 'customer.otp_invalid');

    $this->postJson(H::API.'/auth/logout', [], H::auth($token))->assertNoContent();
    $this->getJson(H::API.'/me', H::auth($token))->assertStatus(401)->assertJsonPath('error.code', 'customer.unauthenticated');
    $this->getJson(H::API.'/me', H::CHANNEL)->assertStatus(401);
});

it('nhập sai 5 lần thì mã bị huỷ (chống brute-force)', function () {
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912345678'], H::CHANNEL)->assertStatus(202);
    $code = FakeOtpSender::$codes['+84912345678|login'];
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $ignored) {
        $this->postJson(H::API.'/auth/otp/verify', ['phone' => '0912345678', 'code' => $wrong], H::CHANNEL)->assertStatus(422);
    }
    $this->postJson(H::API.'/auth/otp/verify', ['phone' => '0912345678', 'code' => $code], H::CHANNEL)->assertStatus(422);
});

it('giới hạn yêu cầu OTP theo SĐT; mã mới vô hiệu mã cũ; không có kênh gửi → otp_unavailable', function () {
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912345678'], H::CHANNEL)->assertStatus(202);
    $first = FakeOtpSender::$codes['+84912345678|login'];
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912345678'], H::CHANNEL)->assertStatus(202);
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912345678'], H::CHANNEL)->assertStatus(202);
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912345678'], H::CHANNEL)->assertStatus(429)->assertJsonPath('error.code', 'customer.otp_rate_limited');

    if ($first !== FakeOtpSender::$codes['+84912345678|login']) {
        $this->postJson(H::API.'/auth/otp/verify', ['phone' => '0912345678', 'code' => $first], H::CHANNEL)->assertStatus(422);
    }

    FakeOtpSender::$available = false;
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0987654321'], H::CHANNEL)->assertStatus(422)->assertJsonPath('error.code', 'customer.otp_unavailable');
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '123'], H::CHANNEL)->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');
});

it('khách vãng lai đặt đơn → profile ẩn; đăng ký cùng SĐT → nhận lại lịch sử đơn', function () {
    Event::fake([CustomerRegistered::class]);
    $number = H::guestOrder($this, $this->s->id, 'guest-order-0001');

    $hidden = Customer::query()->sole();
    expect($hidden->phone)->toBe('+84912345678')
        ->and($hidden->isRegistered())->toBeFalse()
        ->and($hidden->full_name)->toBe('Nguyễn Thị Lan')
        ->and($hidden->email)->toBe('lan@example.com');

    $token = H::login($this, '0912345678');
    expect(Customer::query()->count())->toBe(1);
    Event::assertDispatched(CustomerRegistered::class, fn (CustomerRegistered $event): bool => $event->claimedGuestProfile && $event->customerId === $hidden->id);

    $orders = $this->getJson(H::API.'/me/orders', H::auth($token))->assertOk()->assertJsonPath('meta.total', 1);
    expect($orders->json('data.0.number'))->toBe($number);
    $this->getJson(H::API."/me/orders/{$orders->json('data.0.id')}", H::auth($token))->assertOk()->assertJsonPath('data.number', $number);
});

it('mật khẩu: đặt sau khi đăng nhập OTP, đăng nhập bằng mật khẩu, sai quá 5 lần bị chặn', function () {
    $token = H::login($this);
    $this->putJson(H::API.'/me/password', ['password' => 'mat-khau-123', 'password_confirmation' => 'mat-khau-123'], H::auth($token))->assertNoContent();
    $this->putJson(H::API.'/me/password', ['password' => 'khac-12345', 'password_confirmation' => 'khac-12345'], H::auth($token))
        ->assertStatus(422)->assertJsonPath('error.code', 'customer.password_mismatch');

    $this->postJson(H::API.'/auth/login', ['phone' => '0912345678', 'password' => 'mat-khau-123'], H::CHANNEL)->assertOk()->assertJsonPath('data.has_password', true);
    foreach (range(1, 5) as $ignored) {
        $this->postJson(H::API.'/auth/login', ['phone' => '0912345678', 'password' => 'sai'], H::CHANNEL)->assertStatus(401)->assertJsonPath('error.code', 'customer.invalid_credentials');
    }
    $this->postJson(H::API.'/auth/login', ['phone' => '0912345678', 'password' => 'mat-khau-123'], H::CHANNEL)->assertStatus(429);
});
