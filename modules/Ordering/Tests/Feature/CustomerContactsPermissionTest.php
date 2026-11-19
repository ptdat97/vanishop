<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Support\SimpleXlsx;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Quyền dữ liệu khách trong Admin: `orders.export` tách khỏi `orders.view`; `customers.pii` — không có thì họ tên, SĐT,
| email, số nhà/đường hiện dạng che trên danh sách/chi tiết đơn, file xuất, danh sách/chi tiết khách; phiếu giao bị chặn.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 20);
    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm_mode', 'off'));
    $api = '/api/storefront/v1';
    $created = $this->postJson("{$api}/carts")->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');
    $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)->assertOk();
    $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 600_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();
    $this->order = Order::query()->withoutGlobalScopes()->sole();
    $this->customer = Customer::query()->sole();
    $this->as = fn (array $permissions) => $this->actingAs(T::staff(['admin.access', ...$permissions]), 'staff');
});

it('không có customers.pii: đơn (danh sách, chi tiết) và khách (danh sách, chi tiết, sổ địa chỉ) hiện dạng che; tỉnh/phường vẫn hiện', function () {
    ($this->as)(['orders.view', 'customers.view']);

    $this->get('/admin/orders/orders')->assertInertia(fn (Assert $page) => $page
        ->where('orders.0.customer', 'N. T. Lan')->where('orders.0.phone', '091****678')->where('exportUrl', null));
    $this->get("/admin/orders/orders/{$this->order->id}")->assertInertia(fn (Assert $page) => $page
        ->where('order.customer.full_name', 'N. T. Lan')->where('order.customer.phone', '091****678')->where('order.customer.email', 'l***@example.com')
        ->where('order.shippingAddress.street_line', '***')->where('order.shippingAddress.ward_name', 'Phường Bến Thành')
        ->where('customerHistory.orders_url', null));
    $this->get('/admin/customers')->assertInertia(fn (Assert $page) => $page
        ->where('customers.0.phone', '091****678')->where('customers.0.full_name', 'N. T. Lan')->where('customers.0.contacts_masked', true));
    $this->get("/admin/customers/{$this->customer->public_id}")->assertInertia(fn (Assert $page) => $page
        ->where('customer.email', 'l***@example.com'));
    // Tìm theo SĐT vẫn được (người dùng tự gõ số), kết quả vẫn che.
    $this->get('/admin/orders/orders?q=0912345678')->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.phone', '091****678'));
    $this->get("/admin/orders/orders/print?ids[]={$this->order->id}")->assertForbidden();
});

it('có customers.pii: thấy đủ liên hệ', function () {
    ($this->as)(['orders.view', 'customers.view', 'customers.pii']);

    $this->get("/admin/orders/orders/{$this->order->id}")->assertInertia(fn (Assert $page) => $page
        ->where('order.customer.full_name', 'Nguyễn Thị Lan')->where('order.shippingAddress.street_line', '12 Lê Lợi'));
    $this->get('/admin/customers')->assertInertia(fn (Assert $page) => $page->where('customers.0.full_name', 'Nguyễn Thị Lan')->where('customers.0.contacts_masked', false));
    $this->get("/admin/orders/orders/print?ids[]={$this->order->id}")->assertOk()->assertSee('12 Lê Lợi');
});

it('xuất đơn cần orders.export; không có customers.pii thì cột liên hệ bị che, số liệu đủ; audit ghi có/không liên hệ', function () {
    ($this->as)(['orders.view']);
    $this->get('/admin/orders/orders/export')->assertForbidden();

    ($this->as)(['orders.view', 'orders.export']);
    $this->get('/admin/orders/orders')->assertInertia(fn (Assert $page) => $page->where('exportUrl', route('admin.orders.orders.export')));
    $rows = SimpleXlsx::read($this->get('/admin/orders/orders/export?mode=orders')->assertOk()->getFile()->getPathname());

    expect(array_slice($rows[1], 6, 6))->toBe(['N. T. Lan', '091****678', 'l***@example.com', 'Thành phố Hồ Chí Minh', 'Phường Bến Thành', '***'])
        ->and((int) $rows[1][15])->toBe(600_000)
        ->and(json_decode((string) DB::table('audit_logs')->where('action', 'orders.exported')->value('changes'), true)['contacts'])->toBeFalse();
});
