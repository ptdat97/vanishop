<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Application\CustomerSegmentService;
use Modules\Customer\Contracts\Customers;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Persistence\Models\CustomerGroup;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\Phones;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/CustomerTestHelpers.php';

/*
| Roadmap Phase 8: phân khúc khách (một nhóm/khách + tag) và giá thành viên theo nhóm (price_lists.customer_group_id).
*/

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store(); // giá chung S = 300.000
    $this->vip = T::seed(fn () => CustomerGroup::query()->create(['code' => 'vip', 'name' => 'VIP']));
    T::seed(fn () => PriceList::query()->create([
        'code' => 'vip', 'name' => 'Giá VIP', 'currency_code' => 'VND', 'type' => 'member', 'customer_group_id' => $this->vip->id, 'priority' => 20, 'status' => 'active',
    ])->prices()->create(['variant_id' => $this->s->id, 'amount' => 250_000]));
    $this->signIn = function (string $phone = '0912345678'): Customer {
        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => $phone]);
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString($phone)->e164.'|login']]);

        return Customer::query()->where('phone', Phones::fromString($phone)->e164)->sole();
    };
    $this->admin = fn (array $permissions = ['admin.access', 'customers.view', 'customers.segment']) => $this->actingAs(T::staff($permissions), 'staff');
});

it('khách thuộc nhóm có bảng giá thành viên: giỏ + đơn tính giá thành viên; trang công khai vẫn giá chung, /_vani/gia trả giá thành viên', function () {
    $customer = ($this->signIn)();
    app(CustomerSegmentService::class)->assign($customer, $this->vip->id, []);

    $this->get('/san-pham/'.$this->s->style->slug)->assertOk()->assertSee('300.000')->assertDontSee('250.000');
    $this->getJson('/_vani/gia?v[]='.$this->s->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('group', 'VIP')->assertJsonPath('prices.'.$this->s->id.'.amount', 250_000);

    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->get('/gio-hang')->assertSee('250.000');
    $this->post('/thanh-toan', [
        'contact' => ['full_name' => 'Lan', 'phone' => '0912345678'],
        'shipping_address' => ['province_code' => '01', 'ward_code' => '10105001', 'street_line' => '1 Tràng Tiền'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => 280_000, 'idempotency_key' => 'member-order-0001',
    ])->assertRedirect();

    expect(Order::query()->sole())->subtotal_amount->toBe(250_000)->customer_id->toBe($customer->id);
});

it('khách không nhóm / vãng lai: giá chung; /_vani/gia rỗng', function () {
    $this->getJson('/_vani/gia?v[]='.$this->s->id)->assertJsonPath('prices', []);
    ($this->signIn)();
    $this->getJson('/_vani/gia?v[]='.$this->s->id)->assertJsonPath('group', null)->assertJsonPath('prices', []);
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->get('/gio-hang')->assertSee('300.000');
});

it('Admin: nhóm khách (tạo, sửa, chỉ xoá nhóm rỗng); gán nhóm + tag (chuẩn hoá) cho khách; lọc danh sách theo nhóm/tag', function () {
    $customer = Customer::query()->findOrFail(T::seed(fn () => app(Customers::class)->resolveForCheckout('+84911111111', 'Hà', null)));
    ($this->admin)();

    $this->post('/admin/customer-groups', ['code' => 'si', 'name' => 'Khách sỉ'])->assertSessionHasNoErrors();
    $this->post('/admin/customer-groups', ['code' => 'si', 'name' => 'Trùng'])->assertSessionHasErrors('code');
    $wholesale = CustomerGroup::query()->where('code', 'si')->sole();

    $this->put("/admin/customers/{$customer->public_id}/segment", ['customer_group_id' => $wholesale->id, 'tags' => 'Khách sỉ HN, KOL , kol'])->assertSessionHasNoErrors();
    expect($customer->fresh()->customer_group_id)->toBe($wholesale->id)
        ->and(DB::table('customer_tags')->where('customer_id', $customer->id)->orderBy('tag')->pluck('tag')->all())->toBe(['khach-si-hn', 'kol']);

    $this->get("/admin/customers?group={$wholesale->id}")->assertInertia(fn (Assert $page) => $page->has('customers', 1)->where('customers.0.group', 'Khách sỉ'));
    $this->get('/admin/customers?tag=kol')->assertInertia(fn (Assert $page) => $page->has('customers', 1));
    $this->get('/admin/customers?tag=khong-co')->assertInertia(fn (Assert $page) => $page->has('customers', 0));

    $this->delete("/admin/customer-groups/{$wholesale->id}")->assertSessionHasErrors('group');
    $this->put("/admin/customers/{$customer->public_id}/segment", ['customer_group_id' => null, 'tags' => ''])->assertSessionHasNoErrors();
    $this->delete("/admin/customer-groups/{$wholesale->id}")->assertSessionHasNoErrors();
    expect(DB::table('customer_tags')->where('customer_id', $customer->id)->count())->toBe(0);
});

it('quyền: xem nhóm cần customers.view; sửa nhóm/gán phân khúc cần customers.segment', function () {
    $customer = Customer::query()->findOrFail(T::seed(fn () => app(Customers::class)->resolveForCheckout('+84922222222', 'Minh', null)));
    ($this->admin)(['admin.access', 'customers.view']);

    $this->get('/admin/customer-groups')->assertOk();
    $this->post('/admin/customer-groups', ['code' => 'x', 'name' => 'X'])->assertForbidden();
    $this->put("/admin/customers/{$customer->public_id}/segment", ['customer_group_id' => $this->vip->id])->assertForbidden();
});

it('bảng giá thành viên bắt buộc chọn nhóm khách hợp lệ', function () {
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage']), 'staff');
    $payload = ['code' => 'member-x', 'name' => 'Thành viên', 'type' => 'member', 'priority' => 5, 'status' => 'active'];

    $this->post('/admin/pricing/price-lists', $payload)->assertSessionHasErrors('customer_group_id');
    $this->post('/admin/pricing/price-lists', [...$payload, 'customer_group_id' => 999])->assertSessionHasErrors('customer_group_id');
    $this->post('/admin/pricing/price-lists', [...$payload, 'customer_group_id' => $this->vip->id])->assertSessionHasNoErrors();
    expect(PriceList::query()->where('code', 'member-x')->value('customer_group_id'))->toBe($this->vip->id);
});

it('Storefront API GET /me/prices: giá thành viên cho khách có token; chưa đăng nhập → 401', function () {
    $this->getJson('/api/storefront/v1/me/prices?variant_ids[]='.$this->s->id)->assertUnauthorized();

    $token = H::login($this, '0944444444');
    $customer = Customer::query()->where('phone', '+84944444444')->sole();
    app(CustomerSegmentService::class)->assign($customer, $this->vip->id, []);

    $this->getJson('/api/storefront/v1/me/prices?variant_ids[]='.$this->s->id, H::auth($token))->assertOk()
        ->assertJsonPath('data.group', 'VIP')->assertJsonPath('data.prices.'.$this->s->id.'.amount', 250_000);
});
