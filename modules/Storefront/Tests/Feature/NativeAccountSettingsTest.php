<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Persistence\Models\CustomerAddress;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Shared\Domain\Phone\PhoneNumber;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    C::store();
    $this->signIn = function (string $phone = '0912345678'): Customer {
        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => $phone]);
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[PhoneNumber::fromString($phone)->e164.'|login']])->assertRedirect('/tai-khoan');

        return Customer::query()->where('phone', PhoneNumber::fromString($phone)->e164)->sole();
    };
    $this->address = fn (array $overrides = []): array => [
        'label' => 'Nhà', 'full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678', 'province_code' => '01', 'ward_code' => '10101003',
        'street_line' => '1 Hoàng Diệu', ...$overrides,
    ];
});

it('cần đăng nhập', function () {
    $this->get('/tai-khoan/ho-so')->assertRedirect('/tai-khoan/dang-nhap');
    $this->get('/tai-khoan/dia-chi/them')->assertRedirect('/tai-khoan/dang-nhap');
    $this->put('/tai-khoan/ho-so', ['full_name' => 'X'])->assertRedirect('/tai-khoan/dang-nhap');
});

it('sửa hồ sơ; email của khách khác → lỗi, không đổi', function () {
    $customer = ($this->signIn)();
    Customer::query()->create(['public_id' => (string) Str::ulid(), 'phone' => '+84911111111', 'email' => 'taken@example.com', 'status' => 'active']);

    $this->get('/tai-khoan/ho-so')->assertOk()->assertSee('Hồ sơ')->assertSee('Đặt mật khẩu');
    $this->put('/tai-khoan/ho-so', ['full_name' => 'Trần Lan', 'email' => 'LAN@Example.com', 'birth_date' => '1995-05-20', 'gender' => 'female'])
        ->assertRedirect('/tai-khoan/ho-so')->assertSessionHas('status', 'Đã lưu hồ sơ.');
    expect($customer->fresh())->full_name->toBe('Trần Lan')->email->toBe('lan@example.com')->gender->toBe('female');

    $this->from('/tai-khoan/ho-so')->put('/tai-khoan/ho-so', ['full_name' => 'Trần Lan', 'email' => 'taken@example.com'])->assertSessionHasErrors('business');
    $this->put('/tai-khoan/ho-so', ['full_name' => '', 'gender' => 'robot'])->assertSessionHasErrors(['full_name', 'gender']);
    expect($customer->fresh()->email)->toBe('lan@example.com');
});

it('đặt mật khẩu lần đầu, đổi mật khẩu phải đúng mật khẩu hiện tại', function () {
    $customer = ($this->signIn)();

    $this->put('/tai-khoan/mat-khau', ['password' => 'matkhau-moi-1', 'password_confirmation' => 'khac'])->assertSessionHasErrors('password');
    $this->put('/tai-khoan/mat-khau', ['password' => 'matkhau-moi-1', 'password_confirmation' => 'matkhau-moi-1'])->assertRedirect('/tai-khoan/ho-so');
    expect(Hash::check('matkhau-moi-1', $customer->fresh()->password))->toBeTrue();

    $this->get('/tai-khoan/ho-so')->assertSee('Đổi mật khẩu')->assertSee('Mật khẩu hiện tại');
    $this->put('/tai-khoan/mat-khau', ['password' => 'matkhau-moi-2', 'password_confirmation' => 'matkhau-moi-2'])->assertSessionHasErrors('current_password');
    $this->from('/tai-khoan/ho-so')->put('/tai-khoan/mat-khau', ['current_password' => 'sai-roi-nhe', 'password' => 'matkhau-moi-2', 'password_confirmation' => 'matkhau-moi-2'])->assertSessionHasErrors('business');
    $this->put('/tai-khoan/mat-khau', ['current_password' => 'matkhau-moi-1', 'password' => 'matkhau-moi-2', 'password_confirmation' => 'matkhau-moi-2'])->assertRedirect('/tai-khoan/ho-so');
    expect(Hash::check('matkhau-moi-2', $customer->fresh()->password))->toBeTrue();
});

it('sổ địa chỉ: thêm theo danh mục (tên chuẩn), mã sai bị từ chối, sửa, đặt mặc định, xoá', function () {
    $customer = ($this->signIn)();

    $this->get('/tai-khoan/dia-chi/them')->assertOk()->assertSee('Thành phố Hà Nội')->assertSee('data-province-select', false);
    // Không JS: chọn tỉnh rồi "Tải danh sách phường/xã" → quay lại form, giữ dữ liệu, có danh sách phường.
    $this->from('/tai-khoan/dia-chi/them')->post('/tai-khoan/dia-chi', ['province_code' => '01', 'action' => 'reload'])->assertRedirect('/tai-khoan/dia-chi/them');
    $this->get('/tai-khoan/dia-chi/them')->assertSee('Phường Ba Đình');
    expect(CustomerAddress::query()->count())->toBe(0);

    $this->from('/tai-khoan/dia-chi/them')->post('/tai-khoan/dia-chi', ($this->address)(['ward_code' => '70101065']))->assertSessionHasErrors('business');
    $this->from('/tai-khoan/dia-chi/them')->post('/tai-khoan/dia-chi', ($this->address)(['phone' => '123']))->assertSessionHasErrors('business');
    $this->post('/tai-khoan/dia-chi', ($this->address)())->assertRedirect('/tai-khoan/dia-chi');
    $this->post('/tai-khoan/dia-chi', ($this->address)(['label' => 'Công ty', 'province_code' => '29', 'ward_code' => '70101065', 'street_line' => '12 Lê Lợi']))->assertRedirect('/tai-khoan/dia-chi');

    [$home, $office] = CustomerAddress::query()->where('customer_id', $customer->id)->orderBy('id')->get()->all();
    expect($home)->province_name->toBe('Thành phố Hà Nội')->ward_name->toBe('Phường Ba Đình')->is_default->toBeTrue()
        ->and($office->is_default)->toBeFalse();

    $this->get('/tai-khoan/dia-chi')->assertOk()->assertSee('Phường Bến Thành')->assertSee('Đặt mặc định');
    $this->get("/tai-khoan/dia-chi/{$office->id}/sua")->assertOk()->assertSee('12 Lê Lợi');
    $this->put("/tai-khoan/dia-chi/{$office->id}", ($this->address)(['label' => 'Công ty', 'province_code' => '29', 'ward_code' => '70101065', 'street_line' => '99 Lê Lợi']))->assertRedirect('/tai-khoan/dia-chi');
    expect($office->fresh()->street_line)->toBe('99 Lê Lợi');

    $this->post("/tai-khoan/dia-chi/{$office->id}/mac-dinh")->assertRedirect('/tai-khoan/dia-chi');
    expect($office->fresh()->is_default)->toBeTrue()->and($home->fresh()->is_default)->toBeFalse();

    $this->delete("/tai-khoan/dia-chi/{$office->id}")->assertRedirect('/tai-khoan/dia-chi');
    expect(CustomerAddress::query()->whereKey($office->id)->exists())->toBeFalse()->and($home->fresh()->is_default)->toBeTrue();
});

it('không sửa/xoá được địa chỉ của khách khác', function () {
    $other = Customer::query()->create(['public_id' => (string) Str::ulid(), 'phone' => '+84987654321', 'status' => 'active']);
    $foreign = CustomerAddress::query()->create([
        'customer_id' => $other->id, 'full_name' => 'Khác', 'phone' => '+84987654321', 'province_code' => '01', 'province_name' => 'Thành phố Hà Nội',
        'ward_code' => '10101003', 'ward_name' => 'Phường Ba Đình', 'street_line' => 'x', 'is_default' => true,
    ]);
    ($this->signIn)();

    $this->get("/tai-khoan/dia-chi/{$foreign->id}/sua")->assertNotFound();
    $this->from('/tai-khoan/dia-chi')->delete("/tai-khoan/dia-chi/{$foreign->id}")->assertSessionHasErrors('business');
    $this->from('/tai-khoan/dia-chi')->post("/tai-khoan/dia-chi/{$foreign->id}/mac-dinh")->assertSessionHasErrors('business');
    expect($foreign->fresh())->not->toBeNull();
});
