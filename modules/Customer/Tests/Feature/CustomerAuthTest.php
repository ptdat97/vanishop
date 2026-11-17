<?php

use Illuminate\Support\Facades\Event;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/CustomerTestHelpers.php';

/*
| Đăng nhập mặc định của Core (0.5.12): email + mật khẩu. Đăng ký chỉ gửi link xác minh; tài khoản tạo (hoặc nhận hồ sơ khách
| vãng lai cùng email) khi bấm link. OTP theo SĐT là plugin `vani.phone-otp-login`.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->register = fn (string $email = 'lan@example.com', string $password = 'mat-khau-123') => $this->postJson(H::API.'/auth/register', [
        'full_name' => 'Nguyễn Thị Lan', 'email' => $email, 'password' => $password, 'password_confirmation' => $password,
    ], H::CHANNEL);
});

it('đăng ký: 202 + link xác minh qua email, chưa tạo tài khoản; bấm link → tài khoản + token; /me; đăng xuất thu hồi token', function () {
    Event::fake([CustomerRegistered::class]);
    ($this->register)('Lan@Example.com')->assertStatus(202)->assertJsonPath('data.email', 'lan@example.com')->assertJsonPath('data.expires_in', 86_400);
    expect(Customer::query()->count())->toBe(0);

    $response = $this->postJson(H::API.'/auth/register/verify', ['token' => H::mailedToken('lan@example.com')], H::CHANNEL)->assertOk()
        ->assertJsonPath('data.email', 'lan@example.com')->assertJsonPath('data.phone', null)->assertJsonPath('data.registered', true)
        ->assertJsonPath('data.has_password', true)->assertJsonPath('meta.token_type', 'Bearer');
    $token = $response->json('meta.token');
    expect(Customer::query()->sole())->email_verified_at->not->toBeNull()->full_name->toBe('Nguyễn Thị Lan');
    Event::assertDispatched(CustomerRegistered::class, fn (CustomerRegistered $event): bool => ! $event->claimedGuestProfile);

    $this->getJson(H::API.'/me', H::auth($token))->assertOk()->assertJsonPath('data.id', $response->json('data.id'));
    // Link dùng một lần.
    $this->postJson(H::API.'/auth/register/verify', ['token' => H::mailedToken('lan@example.com')], H::CHANNEL)
        ->assertStatus(422)->assertJsonPath('error.code', 'customer.registration_link_invalid');

    $this->postJson(H::API.'/auth/logout', [], H::auth($token))->assertNoContent();
    $this->getJson(H::API.'/me', H::auth($token))->assertStatus(401)->assertJsonPath('error.code', 'customer.unauthenticated');
});

it('đăng ký: email đã là tài khoản → 409; quá 3 link tới một địa chỉ trong 10 phút → 429; dữ liệu sai → 422', function () {
    H::account('0912345678', 'da-co@example.com');
    ($this->register)('DA-CO@example.com')->assertStatus(409)->assertJsonPath('error.code', 'customer.email_taken');

    foreach (range(1, 3) as $ignored) {
        ($this->register)('moi@example.com')->assertStatus(202);
    }
    ($this->register)('moi@example.com')->assertStatus(429)->assertJsonPath('error.code', 'customer.too_many_attempts');
    ($this->register)('khong-phai-email', 'ngan')->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');
});

it('hai yêu cầu đăng ký cùng email: link bấm trước tạo tài khoản, link còn lại bị từ chối (email đã dùng)', function () {
    ($this->register)('lan@example.com', 'mat-khau-mot')->assertStatus(202);
    $first = H::mailedToken('lan@example.com');
    ($this->register)('lan@example.com', 'mat-khau-hai')->assertStatus(202);
    $second = H::mailedToken('lan@example.com');

    $this->postJson(H::API.'/auth/register/verify', ['token' => $second], H::CHANNEL)->assertOk();
    $this->postJson(H::API.'/auth/register/verify', ['token' => $first], H::CHANNEL)->assertStatus(409)->assertJsonPath('error.code', 'customer.email_taken');
    $this->postJson(H::API.'/auth/login', ['email' => 'lan@example.com', 'password' => 'mat-khau-hai'], H::CHANNEL)->assertOk();
});

it('khách vãng lai đặt đơn → profile ẩn; đăng ký cùng email, bấm link → nhận lại lịch sử đơn', function () {
    Event::fake([CustomerRegistered::class]);
    $number = H::guestOrder($this, $this->s->id, 'guest-order-0001');

    $hidden = Customer::query()->sole();
    expect($hidden->phone)->toBe('+84912345678')
        ->and($hidden->isRegistered())->toBeFalse()
        ->and($hidden->full_name)->toBe('Nguyễn Thị Lan')
        ->and($hidden->email)->toBe('lan@example.com');

    ($this->register)('lan@example.com')->assertStatus(202);
    // Chưa bấm link: chưa ai đăng nhập được vào hồ sơ này.
    expect($hidden->fresh()->isRegistered())->toBeFalse();
    $token = $this->postJson(H::API.'/auth/register/verify', ['token' => H::mailedToken('lan@example.com')], H::CHANNEL)->assertOk()
        ->assertJsonPath('data.phone', '+84912345678')->json('meta.token');
    expect(Customer::query()->count())->toBe(1);
    Event::assertDispatched(CustomerRegistered::class, fn (CustomerRegistered $event): bool => $event->claimedGuestProfile && $event->customerId === $hidden->id);

    $orders = $this->getJson(H::API.'/me/orders', H::auth($token))->assertOk()->assertJsonPath('meta.total', 1);
    expect($orders->json('data.0.number'))->toBe($number);
    $this->getJson(H::API."/me/orders/{$orders->json('data.0.id')}", H::auth($token))->assertOk()->assertJsonPath('data.number', $number);
});

it('khách vãng lai dùng email đã xác minh của tài khoản, SĐT chưa có hồ sơ → đơn vào lịch sử tài khoản; email chưa xác minh thì không', function () {
    $account = H::account('0911111111', 'lan@example.com');
    H::guestOrder($this, $this->s->id, 'guest-verified-0001', ['phone' => '0987654321', 'email' => 'LAN@example.com']);
    expect(Order::query()->withoutGlobalScopes()->sole()->customer_id)->toBe($account->id)
        ->and(Customer::query()->count())->toBe(1)
        ->and($account->fresh()->phone)->toBe('+84911111111');

    $account->update(['email_verified_at' => null]);
    H::guestOrder($this, $this->s->id, 'guest-unverified-0002', ['phone' => '0987654322', 'email' => 'lan@example.com']);
    expect(Order::query()->withoutGlobalScopes()->latest('id')->first()->customer_id)->not->toBe($account->id);

    // SĐT đã có hồ sơ: đơn vẫn theo SĐT (quy tắc cũ), kể cả khi email là của tài khoản đã xác minh.
    $account->update(['email_verified_at' => now()]);
    H::guestOrder($this, $this->s->id, 'guest-known-phone-0003', ['phone' => '0987654322', 'email' => 'lan@example.com']);
    expect(Order::query()->withoutGlobalScopes()->latest('id')->first()->customer_id)->not->toBe($account->id);
});

it('đăng nhập email + mật khẩu (không phân biệt hoa thường); sai quá 5 lần bị chặn; đổi mật khẩu phải đúng mật khẩu hiện tại', function () {
    $customer = H::account();
    $token = $this->postJson(H::API.'/auth/login', ['email' => strtoupper($customer->email), 'password' => H::PASSWORD], H::CHANNEL)->assertOk()
        ->assertJsonPath('data.has_password', true)->json('meta.token');

    // Đã có mật khẩu: thiếu mật khẩu hiện tại → lỗi ở trường đó; nhập sai → lỗi nghiệp vụ (cùng luật với form Blade, ADR-044).
    $this->putJson(H::API.'/me/password', ['password' => 'khac-12345', 'password_confirmation' => 'khac-12345'], H::auth($token))
        ->assertStatus(422)->assertJsonPath('error.code', 'validation.failed')->assertJsonPath('error.details.0.field', 'current_password');
    $this->putJson(H::API.'/me/password', ['current_password' => 'sai-mat-khau', 'password' => 'khac-12345', 'password_confirmation' => 'khac-12345'], H::auth($token))
        ->assertStatus(422)->assertJsonPath('error.code', 'customer.password_mismatch');

    $this->postJson(H::API.'/auth/login', ['email' => 'khong-co@example.com', 'password' => H::PASSWORD], H::CHANNEL)->assertStatus(401)->assertJsonPath('error.code', 'customer.invalid_credentials');
    foreach (range(1, 5) as $ignored) {
        $this->postJson(H::API.'/auth/login', ['email' => $customer->email, 'password' => 'sai'], H::CHANNEL)->assertStatus(401)->assertJsonPath('error.code', 'customer.invalid_credentials');
    }
    $this->postJson(H::API.'/auth/login', ['email' => $customer->email, 'password' => H::PASSWORD], H::CHANNEL)->assertStatus(429)->assertJsonPath('error.code', 'customer.too_many_attempts');
});

it('hồ sơ ẩn (khách vãng lai) chưa đăng ký không đăng nhập được dù có email', function () {
    H::guestOrder($this, $this->s->id, 'guest-no-login-0001');
    Customer::query()->sole()->forceFill(['password' => 'mat-khau-123'])->save();

    $this->postJson(H::API.'/auth/login', ['email' => 'lan@example.com', 'password' => 'mat-khau-123'], H::CHANNEL)->assertStatus(401);
});

it('quên mật khẩu: luôn 202; link đặt lại một lần, thu hồi mọi phiên cũ, xác minh email', function () {
    $customer = H::account();
    $customer->update(['email_verified_at' => null]);
    $old = H::login($this);

    $this->postJson(H::API.'/auth/password/forgot', ['email' => 'khong-co@example.com'], H::CHANNEL)->assertStatus(202);
    $this->postJson(H::API.'/auth/password/forgot', ['email' => $customer->email], H::CHANNEL)->assertStatus(202)->assertJsonPath('data.email', $customer->email);
    $token = H::mailedToken($customer->email);

    $this->postJson(H::API.'/auth/password/reset', ['token' => $token, 'password' => 'moi-12345', 'password_confirmation' => 'khac'], H::CHANNEL)->assertStatus(422);
    $this->postJson(H::API.'/auth/password/reset', ['token' => $token, 'password' => 'moi-12345', 'password_confirmation' => 'moi-12345'], H::CHANNEL)->assertNoContent();
    $this->getJson(H::API.'/me', H::auth($old))->assertStatus(401);
    expect($customer->fresh()->email_verified_at)->not->toBeNull();
    $this->postJson(H::API.'/auth/password/reset', ['token' => $token, 'password' => 'lan-nua-123', 'password_confirmation' => 'lan-nua-123'], H::CHANNEL)
        ->assertStatus(422)->assertJsonPath('error.code', 'customer.password_reset_invalid');

    $this->postJson(H::API.'/auth/login', ['email' => $customer->email, 'password' => 'moi-12345'], H::CHANNEL)->assertOk();
});
