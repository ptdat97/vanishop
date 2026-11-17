<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Customer\Application\AccountLifecycle;
use Modules\Customer\Contracts\AuthProvider;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Testing\AuthProviderContract;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeAuthProvider;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/CustomerTestHelpers.php';

AuthProviderContract::define('fake_id', fn () => new FakeAuthProvider, validParams: fn () => ['code' => 'email'], invalidParams: fn () => ['code' => 'het-han']);

beforeEach(function () {
    config(['vanishop.customer.auth_redirect_uris' => ['https://shop.example']]);
    // `vani.cod` đang bật (plugin hệ thống) — chủ sở hữu giả của nhà cung cấp đăng nhập.
    app(Extensions::class)->contribute(AuthProvider::TAG, FakeAuthProvider::class, 'vani.cod');
    $this->login = function (string $code): TestResponse {
        $state = $this->postJson(H::API.'/auth/social/fake_id/start', ['redirect_uri' => 'https://shop.example/auth/callback'])->assertOk()->json('data.state');

        return $this->postJson(H::API.'/auth/social/fake_id/complete', ['state' => $state, 'params' => ['code' => $code]]);
    };
});

it('liệt kê nhà cung cấp; redirect_uri ngoài danh sách bị chặn; URL mang state', function () {
    $this->getJson(H::API.'/auth/social')->assertOk()->assertJsonPath('data.0.code', 'fake_id');
    $this->postJson(H::API.'/auth/social/fake_id/start', ['redirect_uri' => 'https://evil.example/cb'])->assertStatus(422)->assertJsonPath('error.code', 'customer.social_redirect');
    $this->postJson(H::API.'/auth/social/fake_id/start', ['redirect_uri' => 'https://shop.example.evil.com/cb'])->assertStatus(422);
    $start = $this->postJson(H::API.'/auth/social/fake_id/start', ['redirect_uri' => 'https://shop.example/auth/callback'])->assertOk();
    expect($start->json('data.url'))->toContain($start->json('data.state'));
    $this->postJson(H::API.'/auth/social/khong-co/start', ['redirect_uri' => 'https://shop.example/x'])->assertNotFound();
});

it('email đã xác minh → tạo tài khoản (email đã xác minh), liên kết danh tính; lần sau đăng nhập theo danh tính', function () {
    $token = ($this->login)('email')->assertOk()->json('meta.token');
    $this->getJson(H::API.'/me', H::auth($token))->assertOk()->assertJsonPath('data.email', 'lan@example.com')->assertJsonPath('data.phone', null);

    $customer = Customer::query()->sole();
    expect($customer->isRegistered())->toBeTrue()
        ->and($customer->email_verified_at)->not->toBeNull()
        ->and(DB::table('customer_identities')->where('customer_id', $customer->id)->value('subject'))->toBe('user-email');

    $customer->update(['email' => 'doi@example.com']); // đổi email: vẫn đăng nhập theo danh tính đã liên kết
    ($this->login)('email')->assertOk();
    expect(Customer::query()->count())->toBe(1);
});

it('email đã xác minh ghép với hồ sơ đang có (kể cả khách vãng lai) và đánh dấu email đã xác minh', function () {
    $guest = Customer::query()->create(['public_id' => (string) Str::ulid(), 'phone' => '+84911111111', 'email' => 'lan@example.com', 'status' => 'active']);

    ($this->login)('email')->assertOk()->assertJsonPath('data.phone', '+84911111111');
    expect($guest->fresh())->registered_at->not->toBeNull()->email_verified_at->not->toBeNull()
        ->and(DB::table('customer_identities')->where('subject', 'user-email')->value('customer_id'))->toBe($guest->id);
});

it('chỉ có SĐT đã xác minh: ghép với khách đang có SĐT đó, không tạo tài khoản mới; chưa xác minh → social_email_required', function () {
    ($this->login)('phone')->assertStatus(422)->assertJsonPath('error.code', 'customer.social_email_required');
    ($this->login)('email-unverified')->assertStatus(422)->assertJsonPath('error.code', 'customer.social_email_required');
    expect(Customer::query()->count())->toBe(0);

    $existing = H::account('0912345678');
    ($this->login)('phone-unverified')->assertStatus(422)->assertJsonPath('error.code', 'customer.social_email_required');
    ($this->login)('phone')->assertOk();
    expect(DB::table('customer_identities')->where('subject', 'user-phone')->value('customer_id'))->toBe($existing->id);
});

it('state chỉ dùng một lần và gắn với nhà cung cấp; nhà cung cấp lỗi → social_failed', function () {
    $state = $this->postJson(H::API.'/auth/social/fake_id/start', ['redirect_uri' => 'https://shop.example/auth/callback'])->json('data.state');
    $this->postJson(H::API.'/auth/social/fake_id/complete', ['state' => $state, 'params' => ['code' => 'het-han']])->assertStatus(422)->assertJsonPath('error.code', 'customer.social_failed');
    $this->postJson(H::API.'/auth/social/fake_id/complete', ['state' => $state, 'params' => ['code' => 'phone']])->assertStatus(422)->assertJsonPath('error.code', 'customer.social_state');
    $this->postJson(H::API.'/auth/social/fake_id/complete', ['state' => str_repeat('x', 40), 'params' => ['code' => 'phone']])->assertStatus(422)->assertJsonPath('error.code', 'customer.social_state');
});

it('hợp nhất khách chuyển danh tính liên kết sang khách đích; ẩn danh hoá xoá danh tính', function () {
    ($this->login)('email')->assertOk();
    $source = Customer::query()->sole();
    $target = Customer::query()->create(['public_id' => (string) Str::ulid(), 'phone' => '+84922222222', 'status' => 'active', 'registered_at' => now()]);
    $lifecycle = app(AccountLifecycle::class);
    app(CurrentContext::class)->set(ContextScope::system('test'));

    $lifecycle->merge($source->id, $target->id);
    expect(DB::table('customer_identities')->value('customer_id'))->toBe($target->id)
        ->and($lifecycle->export($target->id)['linked_accounts'][0]['provider'])->toBe('fake_id');

    $lifecycle->anonymize($target->id, 'test');
    expect(DB::table('customer_identities')->count())->toBe(0);
});
