<?php

use Illuminate\Support\Facades\DB;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    app()->tag([FakeApiCarrier::class], CarrierRegistry::CARRIERS_TAG);
    FakeApiCarrier::$failBooking = false;
    FakeApiCarrier::$booked = [];

    ['brand' => $this->brand, 'channel' => $this->channel, 's' => $this->s, 'm' => $this->m, 'location' => $this->location] = C::store();
    $this->api = '/api/storefront/v1';
    $this->headers = ['X-Vani-Channel' => 'web-lumiere'];
    $this->ship = '/admin/fulfillment/lumiere/shipments';
    $this->place = function (?array $lines = null, int $expected = 330_000) {
        $created = $this->postJson("{$this->api}/carts", [], $this->headers)->assertCreated();
        $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        foreach ($lines ?? [[$this->s, 1]] as [$variant, $quantity]) {
            $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $variant->id, 'quantity' => $quantity], $headers)->assertOk();
        }

        return $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => $expected]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();
    };
    $this->order = fn () => Order::query()->withoutGlobalScopes()->latest('id')->first();
    $this->level = fn ($variant, $location = null) => DB::table('stock_levels')->where('variant_id', $variant->id)->where('location_id', ($location ?? $this->location)->id)->first();
    $this->asStaff = fn () => $this->actingAs(T::staffFor($this->brand, ['admin.access', 'fulfillment.view', 'fulfillment.manage', 'orders.view', 'orders.cancel']), 'staff');
});

it('E2E: xem sản phẩm → giỏ → COD → xác nhận → vận đơn → lấy hàng → giao → thu COD → hoàn tất', function () {
    $this->getJson("{$this->api}/products", $this->headers)->assertOk()->assertJsonPath('data.0.in_stock', true);
    $token = ($this->place)()->json('data.access_token');
    $order = ($this->order)();

    // Xác nhận COD tự động → vận đơn tạo tự động từ kho đã giữ hàng.
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    expect($order->order_status->value)->toBe('processing')
        ->and($order->fulfillment_status)->toBe('allocated')
        ->and($shipment->status->value)->toBe('pending_booking')
        ->and($shipment->location_id)->toBe($this->location->id)
        ->and($shipment->cod_amount)->toBe(330_000);

    ($this->asStaff)();
    $this->post("{$this->ship}/{$shipment->id}/book", ['tracking_number' => 'VTP123456', 'service_code' => 'Viettel Post'])->assertSessionHasNoErrors();
    expect(($this->level)($this->s))->reserved->toEqual(1)->on_hand->toEqual(10);

    $this->post("{$this->ship}/{$shipment->id}/status", ['status' => 'picked_up'])->assertSessionHasNoErrors();
    expect(($this->level)($this->s))->reserved->toEqual(0)->on_hand->toEqual(9)
        ->and(($this->order)()->fulfillment_status)->toBe('shipped');

    $this->post("{$this->ship}/{$shipment->id}/status", ['status' => 'in_transit'])->assertSessionHasNoErrors();
    $this->post("{$this->ship}/{$shipment->id}/status", ['status' => 'delivered'])->assertSessionHasNoErrors();
    $this->post("{$this->ship}/{$shipment->id}/status", ['status' => 'in_transit'])->assertSessionHasErrors('business');

    $order = ($this->order)();
    expect($order->fulfillment_status)->toBe('delivered')
        ->and($order->payment_status)->toBe('cod_collected')
        ->and(DB::table('payments')->value('status'))->toBe('paid');

    $this->getJson("{$this->api}/orders/{$order->public_id}", [...$this->headers, 'X-Vani-Order-Token' => $token])->assertOk()
        ->assertJsonPath('data.status.code', 'delivered')
        ->assertJsonPath('data.shipments.0.tracking_number', 'VTP123456')
        ->assertJsonPath('data.shipments.0.status', 'delivered');

    $this->artisan('vani:orders:complete-delivered')->assertSuccessful();
    expect(($this->order)()->order_status->value)->toBe('processing');

    $this->travel(8)->days();
    $this->artisan('vani:orders:complete-delivered')->assertSuccessful();
    expect(($this->order)()->order_status->value)->toBe('completed');
});

it('hàng hoàn về: nhập lại kho, đơn "hoàn về", nhân viên huỷ đơn → huỷ COD', function () {
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    ($this->asStaff)();
    $this->post("{$this->ship}/{$shipment->id}/book", ['tracking_number' => 'GHN1'])->assertSessionHasNoErrors();
    foreach (['picked_up', 'out_for_delivery', 'failed_attempt', 'returning', 'returned'] as $status) {
        $this->post("{$this->ship}/{$shipment->id}/status", ['status' => $status])->assertSessionHasNoErrors();
    }

    $order = ($this->order)();
    expect(($this->level)($this->s)->on_hand)->toEqual(10)
        ->and(DB::table('stock_movements')->where('type', 'return')->count())->toBe(1)
        ->and($order->fulfillment_status)->toBe('returned_to_sender');

    $this->post("/admin/orders/lumiere/orders/{$order->id}/cancel", ['reason' => 'Khách bom hàng'])->assertSessionHasNoErrors();
    expect(($this->order)()->order_status->value)->toBe('cancelled')
        ->and(DB::table('payments')->value('status'))->toBe('cancelled');
});

it('huỷ đơn khi vận đơn chưa lấy hàng → huỷ vận đơn, nhả hàng', function () {
    $token = ($this->place)()->json('data.access_token');
    $order = ($this->order)();

    $this->postJson("{$this->api}/orders/{$order->public_id}/cancel", ['reason' => 'Đổi ý'], [...$this->headers, 'X-Vani-Order-Token' => $token])->assertOk();

    expect(Shipment::query()->withoutGlobalScopes()->sole()->status->value)->toBe('cancelled')
        ->and(($this->level)($this->s)->reserved)->toEqual(0)
        ->and(($this->order)()->fulfillment_status)->toBe('unfulfilled');
});

it('hãng có API: đặt vận đơn tự động sau commit; webhook trùng và sai thứ tự không làm lùi trạng thái', function () {
    config(['vanishop.fulfillment.default_carrier' => 'fake_api']);
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    expect($shipment->status->value)->toBe('created')->and($shipment->tracking_number)->toBe('FK000001');

    $hook = fn (string $status, string $event) => $this->postJson('/api/shipping/fake_api/webhook', FakeApiCarrier::webhook('FK000001', $status, $event));
    $hook('picked_up', 'E1')->assertOk()->assertExactJson(['success' => true]);
    $hook('picked_up', 'E1')->assertOk();
    $hook('delivered', 'E3')->assertOk();
    $hook('in_transit', 'E2')->assertOk();

    expect($shipment->fresh()->status->value)->toBe('delivered')
        ->and(DB::table('shipment_events')->where('shipment_id', $shipment->id)->count())->toBe(5)
        ->and(($this->order)()->payment_status)->toBe('cod_collected');

    $this->postJson('/api/shipping/fake_api/webhook', [...FakeApiCarrier::webhook('FK000001', 'returned', 'E9'), 'status' => 'x'])->assertStatus(400);
    $this->postJson('/api/shipping/fake_api/webhook', FakeApiCarrier::webhook('KHONGCO', 'in_transit', 'E1'))->assertNotFound();
    $this->postJson('/api/shipping/manual/webhook', [])->assertNotFound();
});

it('hãng lỗi khi đặt vận đơn quá số lần thử → booking_failed, nhân viên chuyển sang nhập tay', function () {
    config(['vanishop.fulfillment.default_carrier' => 'fake_api']);
    FakeApiCarrier::$failBooking = true;

    ($this->place)(); // lỗi hãng không làm hỏng phản hồi đặt hàng
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    expect($shipment->status->value)->toBe('pending_booking');
    app(FulfillmentService::class)->bookAutomatically($shipment->id, 1);

    expect($shipment->fresh()->status->value)->toBe('booking_failed')
        ->and($shipment->fresh()->last_error)->toBe('Hãng đang bảo trì');
});

it('đơn giữ hàng ở hai kho → hai vận đơn; chỉ trừ tồn khi cả hai đã rời kho', function () {
    $store = I::location($this->brand, [$this->channel->id], ['code' => 'ST-Q1', 'priority' => -1]);
    I::stock($this->location, $this->s->id, 1);
    I::stock($store, $this->s->id, 5);
    ($this->place)([[$this->s, 3]], 900_000);

    $shipments = Shipment::query()->withoutGlobalScopes()->orderBy('location_id')->get();
    expect($shipments)->toHaveCount(2)
        ->and(DB::table('shipment_lines')->where('shipment_id', $shipments[0]->id)->value('quantity'))->toEqual(1)
        ->and(DB::table('shipment_lines')->where('shipment_id', $shipments[1]->id)->value('quantity'))->toEqual(2)
        ->and($shipments[0]->cod_amount + $shipments[1]->cod_amount)->toBe(900_000);

    ($this->asStaff)();
    foreach ($shipments as $index => $shipment) {
        $this->post("{$this->ship}/{$shipment->id}/book", ['tracking_number' => "T{$index}"])->assertSessionHasNoErrors();
    }
    $this->post("{$this->ship}/{$shipments[0]->id}/status", ['status' => 'picked_up']);
    expect(($this->order)()->fulfillment_status)->toBe('partially_shipped')
        ->and((int) DB::table('stock_levels')->sum('reserved'))->toBe(3);

    $this->post("{$this->ship}/{$shipments[1]->id}/status", ['status' => 'picked_up']);
    expect(($this->order)()->fulfillment_status)->toBe('shipped')
        ->and((int) DB::table('stock_levels')->sum('reserved'))->toBe(0)
        ->and(($this->level)($this->s, $store)->on_hand)->toEqual(3);
});

it('Admin: quyền, cô lập brand, mã vận đơn trùng', function () {
    ($this->place)();
    ($this->place)();
    [$first, $second] = Shipment::query()->withoutGlobalScopes()->orderBy('id')->get()->all();

    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'fulfillment.view']), 'staff');
    $this->get($this->ship)->assertOk();
    $this->post("{$this->ship}/{$first->id}/book", ['tracking_number' => 'X1'])->assertForbidden();

    ($this->asStaff)();
    $this->post("{$this->ship}/{$first->id}/book", ['tracking_number' => 'X1'])->assertSessionHasNoErrors();
    $this->post("{$this->ship}/{$second->id}/book", ['tracking_number' => 'X1'])->assertSessionHasErrors('business');

    $other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->actingAs(T::staffFor($other, ['admin.access', 'fulfillment.view', 'fulfillment.manage']), 'staff');
    $this->post("/admin/fulfillment/urbanx/shipments/{$first->id}/book", ['tracking_number' => 'X9'])->assertNotFound();
});
