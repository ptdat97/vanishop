<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\CustomerNotes;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| EP v3 X6 — plugin ghi nhãn/nhóm khách (CustomerSegments) và ghi chú CSKH (CustomerNotes); thẻ "Ghi chú CSKH" trên trang đơn.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 10);
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'order-key-0001'])->assertCreated();
    $this->order = Order::query()->withoutGlobalScopes()->sole();
    $this->customer = Customer::query()->whereKey($this->order->customer_id)->sole();
});

it('nhãn + nhóm khách do plugin ghi: chuẩn hoá, không trùng, audit kèm nguồn; nhóm không tồn tại bị từ chối', function () {
    $segments = app(CustomerSegments::class);
    $group = T::seed(fn () => DB::table('customer_groups')->insertGetId(['code' => 'vip', 'name' => 'VIP', 'created_at' => now(), 'updated_at' => now()]));

    $segments->tag($this->customer->id, 'Bom Hàng', 'system:vani.cod-risk-guard');
    $segments->tag($this->customer->id, 'bom-hang', 'system:vani.cod-risk-guard');
    $segments->tag($this->customer->id, 'vip', 'staff');
    $segments->untag($this->customer->id, 'vip', 'staff');
    $segments->assignGroup($this->customer->id, $group, 'system:vani.loyalty');

    expect($segments->segmentOf($this->customer->id)->tags)->toBe(['bom-hang'])
        ->and($this->customer->fresh()->customer_group_id)->toBe($group)
        ->and(DB::table('audit_logs')->where('action', 'customer.tagged')->count())->toBe(2)
        ->and(fn () => $segments->assignGroup($this->customer->id, 999_999, 'staff'))->toThrow(ValidationException::class);
});

it('ghi chú CSKH: plugin + nhân viên; thẻ trên trang đơn hiện ghi chú gần nhất và thêm được', function () {
    app(CustomerNotes::class)->add($this->customer->id, 'Khách gọi hỏi đổi size', 'system:vani.hello-world');
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'customers.view', 'customers.segment']), 'staff');

    $panel = collect($this->get("/admin/orders/orders/{$this->order->id}")->viewData('page')['props']['panels'])->firstWhere('title', 'Ghi chú CSKH');
    expect($panel['rows'][0]['value'])->toBe('Khách gọi hỏi đổi size (system:vani.hello-world)');
    $this->post($panel['actions'][0]['url'], ['note' => 'Hẹn giao sau 18h'])->assertSessionHas('success', 'Đã thêm ghi chú.');

    expect(array_column(app(CustomerNotes::class)->latest($this->customer->id), 'note'))->toBe(['Hẹn giao sau 18h', 'Khách gọi hỏi đổi size'])
        ->and($this->get("/admin/customers/{$this->customer->public_id}")->viewData('page')['props']['notes'][0]['source'])->toBe('staff');
});
