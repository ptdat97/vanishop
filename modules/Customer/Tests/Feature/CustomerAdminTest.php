<?php

use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Events\CustomerMerged;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    H::guestOrder($this, $this->s->id, 'admin-order-1');
    H::guestOrder($this, $this->s->id, 'admin-order-2', ['phone' => '0987654321', 'full_name' => 'Lan (số phụ)', 'email' => '']);
    $this->main = Customer::query()->where('phone', '+84912345678')->sole();
    $this->dupe = Customer::query()->where('phone', '+84987654321')->sole();
});

it('Owner tìm, xem, hợp nhất khách trùng (chuyển đơn, phát CustomerMerged) và ẩn danh hoá', function () {
    Event::fake([CustomerMerged::class]);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'customers.view', 'customers.merge', 'customers.anonymize'])->create(), 'staff');

    $this->get('/admin/customers?q=0987654321')->assertInertia(fn (Assert $page) => $page->component('Customer::Customers/Index')
        ->has('customers', 1)->where('customers.0.id', $this->dupe->public_id));
    $this->get("/admin/customers/{$this->main->public_id}")->assertInertia(fn (Assert $page) => $page->component('Customer::Customers/Show')
        ->has('orders', 1)->where('stats.orders_count', 1)->where('can.merge', true));

    $this->post("/admin/customers/{$this->dupe->public_id}/merge", ['target' => $this->main->public_id])
        ->assertRedirect("/admin/customers/{$this->main->public_id}");

    expect(Order::query()->withoutGlobalScopes()->where('customer_id', $this->main->id)->count())->toBe(2)
        ->and($this->dupe->fresh()->status->value)->toBe('merged')
        ->and($this->dupe->fresh()->merged_into_id)->toBe($this->main->id)
        ->and((int) T::seed(fn () => Order::query()->where('customer_id', $this->main->id)->count()))->toBe(2)
        ->and(AuditLog::query()->where('action', 'customer.merged')->count())->toBe(1);
    Event::assertDispatched(CustomerMerged::class, fn (CustomerMerged $event): bool => $event->movedOrders === 1);

    // Khách đã merge không merge lại được; SĐT cũ được giải phóng cho hồ sơ mới.
    $this->post("/admin/customers/{$this->dupe->public_id}/merge", ['target' => $this->main->public_id])->assertSessionHasErrors();
    H::login($this, '0987654321');
    expect(Customer::query()->where('phone', '+84987654321')->where('status', 'active')->count())->toBe(1);

    $this->post("/admin/customers/{$this->main->public_id}/anonymize")->assertSessionHasNoErrors();
    expect($this->main->fresh()->status->value)->toBe('anonymized');
});

it('không có quyền merge/ẩn danh → 403', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'customers.view'])->create(), 'staff');
    $this->get('/admin/customers')->assertOk();
    $this->post("/admin/customers/{$this->dupe->public_id}/merge", ['target' => $this->main->public_id])->assertForbidden();
    $this->post("/admin/customers/{$this->main->public_id}/anonymize")->assertForbidden();
});
