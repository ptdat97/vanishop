<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\NotificationCatalog;
use Modules\Notification\Contracts\Notifier;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Đồng ý nhận tin marketing qua email (Nghị định 91/2020): khách tự tích ở đăng ký, checkout, hồ sơ; mọi email marketing có
| link huỷ có chữ ký. Tin nhắc giỏ bỏ quên của plugin dựa vào các điểm này.
*/

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    $this->consent = fn (int $customerId): bool => app(CustomerAccounts::class)->marketingConsent($customerId, 'email');
    $this->source = fn (int $customerId): ?string => DB::table('customer_consent_events')->where('customer_id', $customerId)->latest('id')->value('source');
});

it('đăng ký có tích "Nhận ưu đãi qua email": đồng ý chỉ ghi khi bấm link xác minh; không tích thì không có', function () {
    $this->get('/tai-khoan/dang-ky')->assertOk()->assertSee('name="marketing_email"', false);
    $this->post('/tai-khoan/dang-ky', ['full_name' => 'Lan', 'email' => 'lan@example.com', 'phone' => '0987100001', 'password' => 'mat-khau-123', 'password_confirmation' => 'mat-khau-123', 'marketing_email' => '1'])
        ->assertRedirect('/tai-khoan/dang-ky');
    expect(DB::table('customer_consents')->count())->toBe(0);

    $this->get(H::mailedLink('lan@example.com'))->assertRedirect('/tai-khoan');
    $lan = Customer::query()->where('email', 'lan@example.com')->sole();
    expect(($this->consent)($lan->id))->toBeTrue()->and(($this->source)($lan->id))->toBe('storefront:register');

    $this->post('/tai-khoan/dang-xuat');
    $this->post('/tai-khoan/dang-ky', ['full_name' => 'Mai', 'email' => 'mai@example.com', 'phone' => '0987100002', 'password' => 'mat-khau-123', 'password_confirmation' => 'mat-khau-123']);
    $this->get(H::mailedLink('mai@example.com'));
    expect(($this->consent)(Customer::query()->where('email', 'mai@example.com')->value('id')))->toBeFalse();
});

it('checkout có tích → ghi đồng ý cho khách của đơn; không tích thì giữ nguyên', function () {
    $payload = [
        'contact' => ['full_name' => 'Lan', 'phone' => '0912345678', 'email' => 'lan@example.com'],
        'shipping_address' => ['province_code' => '01', 'ward_code' => '10105001', 'street_line' => '1 Tràng Tiền'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => 330_000,
    ];
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->get('/thanh-toan')->assertSee('name="marketing_email"', false);
    $this->post('/thanh-toan', [...$payload, 'idempotency_key' => 'consent-0001'])->assertSessionHasNoErrors();
    $customerId = (int) Customer::query()->where('phone', '+84912345678')->value('id');
    expect(($this->consent)($customerId))->toBeFalse();

    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', [...$payload, 'marketing_email' => '1', 'idempotency_key' => 'consent-0002'])->assertSessionHasNoErrors();
    expect(($this->consent)($customerId))->toBeTrue()->and(($this->source)($customerId))->toBe('storefront:checkout');
});

it('trang hồ sơ bật/tắt nhận ưu đãi qua email', function () {
    $customer = H::account('0912345678');
    $this->post('/tai-khoan/dang-nhap', ['email' => $customer->email, 'password' => H::PASSWORD]);

    $this->get('/tai-khoan/ho-so')->assertOk()->assertSee('data-account-marketing', false);
    $this->put('/tai-khoan/nhan-tin', ['channel' => 'email', 'purpose' => 'marketing', 'granted' => '1'])->assertRedirect('/tai-khoan/ho-so')->assertSessionHas('status', 'Đã bật nhận ưu đãi qua email.');
    expect(($this->consent)($customer->id))->toBeTrue();
    $this->put('/tai-khoan/nhan-tin', ['channel' => 'email', 'purpose' => 'marketing', 'granted' => '0'])->assertSessionHas('status', 'Đã tắt nhận ưu đãi qua email.');
    expect(($this->consent)($customer->id))->toBeFalse()->and(($this->source)($customer->id))->toBe('storefront:account');
});

it('link huỷ nhận tin: có chữ ký, mở link chỉ hỏi lại, bấm nút mới huỷ; link bị sửa → 403', function () {
    $customer = H::account('0912345678');
    $accounts = app(CustomerAccounts::class);
    $accounts->setMarketingConsent($customer->id, 'email', true, 'test');
    $url = $accounts->unsubscribeUrl($customer->id, 'email');
    expect($url)->toContain('/tai-khoan/huy-nhan-tin/'.$customer->public_id.'/email')->toContain('signature=');

    $this->get($url)->assertOk()->assertSee('Huỷ nhận email ưu đãi')->assertSee('Huỷ nhận email');
    expect(($this->consent)($customer->id))->toBeTrue();
    $this->get(str_replace('/email?', '/sms?', $url))->assertForbidden();

    $this->post($url)->assertOk()->assertSee('Đã huỷ.');
    expect(($this->consent)($customer->id))->toBeFalse()->and(($this->source)($customer->id))->toBe('unsubscribe_link');
});

it('email marketing luôn có link huỷ (mẫu không đặt thì Core tự thêm); tin giao dịch không có', function () {
    $customer = H::account('0912345678');
    app(CustomerAccounts::class)->setMarketingConsent($customer->id, 'email', true, 'test');
    app(NotificationCatalog::class)->define(new NotificationType('test_promo', 'Thử ưu đãi', ['name'], ['mail' => ['subject' => 'Ưu đãi', 'body' => 'Chào {{ name }}']]));
    $recipient = new Recipient(email: $customer->email, name: 'Lan', customerId: $customer->id);

    app(Notifier::class)->notify(new NotificationRequest('test_promo', 'promo:1', $recipient, ['name' => 'Lan'], NotificationRequest::MARKETING));
    app(Notifier::class)->notify(new NotificationRequest('test_promo', 'promo:2', $recipient, ['name' => 'Lan']));

    $bodies = DB::table('notification_logs')->where('type', 'test_promo')->orderBy('id')->pluck('body')->all();
    expect($bodies[0])->toStartWith('Chào Lan')->toContain('Huỷ nhận tin tại: '.url('/tai-khoan/huy-nhan-tin/'.$customer->public_id.'/email'))
        ->and($bodies[1])->toBe('Chào Lan');
});
