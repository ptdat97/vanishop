<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $s] = C::store();
    // Đơn COD 2 sản phẩm, đã giao.
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $s->id, 'quantity' => 2], $headers)->assertOk();
    $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 600_000]), [...$headers, 'Idempotency-Key' => 'staff-return-1'])->assertCreated();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        $shipment = Shipment::query()->sole();
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'T1');
        $service->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'e1', 'staff');
        $service->updateStatus($shipment->id, ShipmentStatus::Delivered, 'e2', 'staff');
    });
    $this->order = Order::query()->withoutGlobalScopes()->sole();
    $this->lineId = $this->order->lines()->value('id');
});

it('nhân viên tạo yêu cầu đổi/trả hộ khách: nguồn staff, theo số còn trả được; panel đơn có link tạo', function () {
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'returns.view', 'returns.manage']), 'staff');

    $this->get("/admin/orders/orders/{$this->order->id}")->assertInertia(fn (Assert $page) => $page
        ->where('panels', fn ($panels) => collect($panels)->contains(fn ($panel) => $panel['title'] === 'Đổi/trả' && str_ends_with($panel['link']['url'], "/returns/create/{$this->order->id}"))));
    $this->get("/admin/returns/returns/create/{$this->order->id}")->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Returns::Returns/Create')->where('lines.0.returnable', 2));

    $this->post('/admin/returns/returns', ['order_id' => $this->order->id, 'lines' => [$this->lineId => 3], 'reason_code' => 'defective'])->assertSessionHasErrors('business');
    $this->post('/admin/returns/returns', ['order_id' => $this->order->id, 'lines' => [$this->lineId + 999 => 1], 'reason_code' => 'defective'])->assertSessionHasErrors('business');
    $this->post('/admin/returns/returns', ['order_id' => $this->order->id, 'lines' => [$this->lineId => 1], 'reason_code' => 'robot'])->assertSessionHasErrors('reason_code');

    $response = $this->post('/admin/returns/returns', ['order_id' => $this->order->id, 'lines' => [$this->lineId => 2], 'reason_code' => 'defective', 'note' => 'Khách gọi: đường may bung']);
    $return = ReturnRequest::query()->sole();
    $response->assertRedirect("/admin/returns/returns/{$return->id}");
    expect([$return->source, $return->reason_code, $return->customer_note, $return->lines()->value('quantity')])->toBe(['staff', 'defective', 'Khách gọi: đường may bung', 2]);

    // Hết hàng trả được → panel chuyển sang mở yêu cầu đang có.
    $this->get("/admin/orders/orders/{$this->order->id}")->assertInertia(fn (Assert $page) => $page
        ->where('panels', fn ($panels) => collect($panels)->contains(fn ($panel) => $panel['title'] === 'Đổi/trả' && $panel['link']['label'] === 'Xử lý đổi/trả')));
});

it('cần quyền returns.manage', function () {
    $this->actingAs(T::staff(['admin.access', 'returns.view']), 'staff');

    $this->get("/admin/returns/returns/create/{$this->order->id}")->assertForbidden();
    $this->post('/admin/returns/returns', ['order_id' => $this->order->id, 'lines' => [$this->lineId => 1], 'reason_code' => 'defective'])->assertForbidden();
});
