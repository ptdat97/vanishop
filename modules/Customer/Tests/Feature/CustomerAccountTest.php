<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->token = H::login($this);
    $this->address = fn (array $overrides = []): array => [
        'full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678', 'province_code' => '79', 'province_name' => 'TP. Hồ Chí Minh',
        'ward_code' => '26734', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi', ...$overrides,
    ];
});

it('cập nhật hồ sơ; email đã thuộc khách khác → 409', function () {
    $this->patchJson(H::API.'/me', ['full_name' => 'Lan Nguyễn', 'email' => 'Lan@Example.com', 'gender' => 'female', 'birth_date' => '1995-03-08'], H::auth($this->token))
        ->assertOk()->assertJsonPath('data.email', 'lan@example.com')->assertJsonPath('data.birth_date', '1995-03-08');

    $other = H::login($this, '0987654321');
    $this->patchJson(H::API.'/me', ['email' => 'LAN@example.com'], H::auth($other))->assertStatus(409)->assertJsonPath('error.code', 'customer.email_taken');
});

it('sổ địa chỉ: địa chỉ đầu là mặc định, đổi mặc định, xoá mặc định thì địa chỉ khác lên thay, không đụng được địa chỉ của khách khác', function () {
    $first = $this->postJson(H::API.'/me/addresses', ($this->address)(), H::auth($this->token))->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');
    $second = $this->postJson(H::API.'/me/addresses', ($this->address)(['label' => 'Công ty', 'phone' => '+84 28 3822 1234']), H::auth($this->token))
        ->assertCreated()->assertJsonPath('data.is_default', false)->json('data.id');
    $this->patchJson(H::API."/me/addresses/{$second}", ['is_default' => true, 'street_line' => '1 Nguyễn Huệ'], H::auth($this->token))->assertOk()
        ->assertJsonPath('data.is_default', true)->assertJsonPath('data.street_line', '1 Nguyễn Huệ');

    $list = $this->getJson(H::API.'/me/addresses', H::auth($this->token))->json('data');
    expect(array_column($list, 'id'))->toBe([$second, $first]);

    $this->deleteJson(H::API."/me/addresses/{$second}", [], H::auth($this->token))->assertNoContent();
    expect($this->getJson(H::API.'/me/addresses', H::auth($this->token))->json('data.0.is_default'))->toBeTrue();

    $other = H::login($this, '0987654321');
    $this->deleteJson(H::API."/me/addresses/{$first}", [], H::auth($other))->assertStatus(404);
    $this->postJson(H::API.'/me/addresses', ($this->address)(['phone' => 'abc']), H::auth($this->token))->assertStatus(422);
});

it('consent: cấp/rút, ghi ledger, trạng thái không đổi thì không ghi thêm', function () {
    $body = ['channel' => 'email', 'purpose' => 'marketing', 'granted' => true];
    $this->putJson(H::API.'/me/consents', $body, H::auth($this->token))->assertOk()->assertJsonPath('data.0.granted', true);
    $this->putJson(H::API.'/me/consents', $body, H::auth($this->token))->assertOk();
    $this->putJson(H::API.'/me/consents', [...$body, 'granted' => false], H::auth($this->token))->assertOk()->assertJsonPath('data.0.granted', false);

    expect(DB::table('customer_consent_events')->pluck('action')->all())->toBe(['granted', 'revoked']);

    // Kênh của plugin mới (vd. web push) dùng được consent mà không sửa Core; mã sai định dạng bị chặn.
    $this->putJson(H::API.'/me/consents', [...$body, 'channel' => 'webpush'], H::auth($this->token))->assertOk();
    $this->putJson(H::API.'/me/consents', [...$body, 'channel' => 'Web Push!'], H::auth($this->token))->assertStatus(422);
});

it('xuất dữ liệu cá nhân gồm hồ sơ, địa chỉ, consent, đơn hàng', function () {
    H::guestOrder($this, $this->s->id, 'export-order-1');
    $this->postJson(H::API.'/me/addresses', ($this->address)(), H::auth($this->token))->assertCreated();

    $export = $this->getJson(H::API.'/me/export', H::auth($this->token))->assertOk()->json('data');
    expect($export['profile']['phone'])->toBe('+84912345678')
        ->and($export['addresses'])->toHaveCount(1)
        ->and($export['orders'])->toHaveCount(1)
        ->and($export['orders'][0]['total'])->toBe(330_000);
});

it('xoá tài khoản = ẩn danh hoá (cần OTP): xoá PII, thu hồi phiên, giữ đơn; SĐT dùng lại được cho tài khoản mới', function () {
    H::guestOrder($this, $this->s->id, 'delete-order-1');
    $customer = Customer::query()->sole();

    $this->postJson(H::API.'/me/delete', ['code' => '000000'], H::auth($this->token))->assertStatus(422);
    $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912345678', 'purpose' => 'delete_account'], H::CHANNEL)->assertStatus(202);
    $this->postJson(H::API.'/me/delete', ['code' => FakeOtpSender::$codes['+84912345678|delete_account']], H::auth($this->token))->assertNoContent();

    $customer->refresh();
    expect($customer->status->value)->toBe('anonymized')
        ->and($customer->phone)->toBeNull()
        ->and($customer->full_name)->toBeNull()
        ->and(Order::query()->withoutGlobalScopes()->where('customer_id', $customer->id)->count())->toBe(1);
    $this->getJson(H::API.'/me', H::auth($this->token))->assertStatus(401);

    H::login($this, '0912345678');
    expect(Customer::query()->count())->toBe(2);
});
