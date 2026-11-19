<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Persistence\Models\CustomerAddress;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Form checkout (stabilization S2): điền sẵn từ tài khoản, sổ địa chỉ, tóm tắt lỗi đầu form (render ở server).
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->signIn = function (): Customer {
        $customer = H::account('0912345678');
        $customer->update(['full_name' => 'Nguyễn Thị Lan']);
        $this->post('/tai-khoan/dang-nhap', ['email' => $customer->email, 'password' => H::PASSWORD]);

        return $customer;
    };
    $this->address = fn (Customer $customer, array $attributes): CustomerAddress => CustomerAddress::query()->create([
        'customer_id' => $customer->id, 'label' => 'Nhà', 'full_name' => 'Trần Văn Bình', 'phone' => '+84987654321',
        'province_code' => '01', 'province_name' => 'Thành phố Hà Nội', 'ward_code' => '10101003', 'ward_name' => 'Phường Ba Đình',
        'street_line' => '1 Hoàng Diệu', 'is_default' => false, ...$attributes,
    ]);
    $this->form = fn (array $overrides = []): array => array_replace_recursive([
        'contact' => ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678', 'email' => ''],
        'shipping_address' => ['province_code' => '29', 'ward_code' => '70101065', 'street_line' => '12 Lê Lợi'],
        'shipping_method' => 'standard', 'payment_method' => 'cod',
        'expected_total' => 330_000, 'idempotency_key' => 'checkout-form-0001',
    ], $overrides);
});

it('khách đăng nhập: điền sẵn người nhận + địa chỉ mặc định, chọn sẵn tỉnh/phường; sổ địa chỉ có mọi địa chỉ', function () {
    $customer = ($this->signIn)();
    ($this->address)($customer, ['is_default' => true]);
    ($this->address)($customer, ['label' => 'Công ty', 'street_line' => '9 Lê Duẩn']);
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);

    $html = $this->get('/thanh-toan')->assertOk()->getContent();

    expect($html)->toContain('value="Trần Văn Bình"')
        ->toContain('value="+84987654321"')
        ->toContain('value="'.$customer->email.'"')
        ->toContain('value="1 Hoàng Diệu"')
        ->toMatch('/<option value="01"\s+selected/')
        ->toMatch('/<option value="10101003"\s+selected/')
        ->toContain('data-checkout-saved-address')
        ->toContain('Công ty · Trần Văn Bình · 9 Lê Duẩn');
});

it('khách chưa có địa chỉ: điền họ tên, SĐT, email từ hồ sơ; một địa chỉ thì không hiện ô chọn sổ địa chỉ', function () {
    $customer = ($this->signIn)();
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);

    $this->get('/thanh-toan')->assertOk()
        ->assertSee('value="Nguyễn Thị Lan"', false)
        ->assertSee('value="'.$customer->phone.'"', false)
        ->assertDontSee('data-checkout-saved-address', false);
});

it('khách vãng lai: form trống', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);

    $this->get('/thanh-toan')->assertOk()->assertDontSee('value="Nguyễn Thị Lan"', false)->assertDontSee('data-checkout-errors', false);
});

it('gửi lỗi: giá trị khách vừa gõ thắng giá trị điền sẵn; lỗi theo ô có tóm tắt đầu form với link tới ô', function () {
    $customer = ($this->signIn)();
    ($this->address)($customer, ['is_default' => true]);
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);

    $this->post('/thanh-toan', ($this->form)(['contact' => ['full_name' => 'Lê Hoa', 'phone' => '123']]))->assertRedirect('/thanh-toan');
    $html = $this->get('/thanh-toan')->assertOk()->getContent();

    expect($html)->toContain('value="Lê Hoa"')->toContain('value="123"')->not->toContain('value="Trần Văn Bình"')
        ->toContain('data-checkout-errors')
        ->toContain('href="#contact.phone"')
        ->toContain('aria-invalid="true" aria-describedby="contact.phone-error"');
    expect(Order::query()->count())->toBe(0);
});

it('lỗi chung (tổng đã đổi) hiện trong tóm tắt lỗi ngay trong HTML — không cần JS', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);

    $this->post('/thanh-toan', ($this->form)(['expected_total' => 1]))->assertRedirect('/thanh-toan');

    $this->get('/thanh-toan')->assertOk()
        ->assertSeeInOrder(['id="checkout-errors" role="alert"', 'Tổng tiền đơn hàng đã thay đổi'], false)
        ->assertDontSee('hidden data-checkout-errors', false);
    expect(Order::query()->count())->toBe(0);
});
