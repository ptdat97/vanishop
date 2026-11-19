<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Contracts\FulfillmentRejected;
use Modules\Fulfillment\Contracts\ShipmentBooking;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiRates;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Events\OrderCompleted;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    C::autoConfirmCod();
    app(Extensions::class)->tag([FakeApiCarrier::class], CarrierRegistry::CARRIERS_TAG);
    FakeApiCarrier::$failBooking = false;
    FakeApiCarrier::$duringBooking = null;
    FakeApiCarrier::$booked = [];
    FakeApiCarrier::$returnPickups = [];

    ['brand' => $this->brand, 's' => $this->s, 'm' => $this->m, 'location' => $this->location] = C::store();
    $this->api = '/api/storefront/v1';
    $this->headers = [];
    $this->ship = '/admin/fulfillment/shipments';
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
    $this->asStaff = fn () => $this->actingAs(T::staff(['admin.access', 'fulfillment.view', 'fulfillment.manage', 'orders.view', 'orders.cancel']), 'staff');
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
    $completed = [];
    Event::listen(OrderCompleted::class, function (OrderCompleted $event) use (&$completed): void {
        $completed[] = $event;
    });
    $this->artisan('vani:orders:complete-delivered')->assertSuccessful();
    expect(($this->order)()->order_status->value)->toBe('completed')
        ->and($completed)->toHaveCount(1)
        ->and($completed[0]->number)->toBe($order->number)
        ->and($completed[0]->total)->toBe(330_000);
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

    $this->post("/admin/orders/orders/{$order->id}/cancel", ['reason' => 'Khách bom hàng'])->assertSessionHasNoErrors();
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

it('hãng có API, booking = on_create (điểm mở rộng): đặt vận đơn tự động sau commit; webhook trùng và sai thứ tự không làm lùi trạng thái', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    app(Settings::class)->set('core', 'fulfillment.booking', 'on_create');
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

it('hãng có API, mặc định booking = manual (ADR-046 chốt ②): xác nhận không gọi hãng; nhân viên "Đẩy lên hãng" hàng loạt; bấm trùng không đặt hai lần; hãng nhập tay bị bỏ qua', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    ($this->place)();
    $api = Shipment::query()->withoutGlobalScopes()->sole();
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'manual');
    ($this->place)();
    $manual = Shipment::query()->withoutGlobalScopes()->whereKeyNot($api->id)->sole();
    ($this->asStaff)();
    expect($api->status->value)->toBe('pending_booking')->and(FakeApiCarrier::$booked)->toBe([]);
    $this->get("/admin/orders/orders/{$api->order_id}")->assertInertia(fn (Assert $page) => $page->where('panels', fn ($panels) => collect($panels)->flatMap(fn ($panel) => $panel['actions'] ?? [])->contains(fn ($action) => $action['key'] === "{$api->id}-push" && $action['primary'])));

    $this->post("{$this->ship}/bulk-push", ['ids' => [$api->order_id, $manual->order_id]])
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Đã đẩy 1 vận đơn') && str_contains($message, 'Bỏ qua 1'));
    $this->post("{$this->ship}/bulk-push", ['ids' => [$api->order_id]])->assertSessionHas('success', fn (string $message) => str_contains($message, 'Đã đẩy 0 vận đơn'));

    expect($api->fresh()->status->value)->toBe('created')->and($api->fresh()->tracking_number)->toBe('FK000001')
        ->and(FakeApiCarrier::$booked)->toHaveCount(1)
        ->and($manual->fresh()->status->value)->toBe('pending_booking');
    $this->post("{$this->ship}/{$api->id}/push")->assertSessionHasErrors('business');
});

it('đẩy lên hãng bị lỗi → booking_failed; "Đẩy lại lên hãng" trên trang đơn đặt lại từ đầu; plugin gọi ShipmentBooking cùng kết quả', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    FakeApiCarrier::$failBooking = true;
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    app(FulfillmentService::class)->bookAutomatically($shipment->id, 1);
    ($this->asStaff)();
    $this->get("/admin/orders/orders/{$shipment->order_id}")->assertInertia(fn (Assert $page) => $page->where('panels', fn ($panels) => collect($panels)->flatMap(fn ($panel) => $panel['actions'] ?? [])->contains(fn ($action) => $action['key'] === "{$shipment->id}-push" && $action['label'] === 'Đẩy lại lên hãng')));
    FakeApiCarrier::$failBooking = false;

    $this->post("{$this->ship}/{$shipment->id}/push")->assertSessionHasNoErrors();

    expect($shipment->fresh()->status->value)->toBe('created')
        ->and(($this->order)()->delivery_issue)->toBeNull()
        ->and(app(ShipmentBooking::class)->pushToCarrier([$shipment->id]))->toBe(0);
});

it('hãng lỗi khi đặt vận đơn quá số lần thử → booking_failed, nhân viên chuyển sang nhập tay', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    FakeApiCarrier::$failBooking = true;

    ($this->place)(); // lỗi hãng không làm hỏng phản hồi đặt hàng
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    expect($shipment->status->value)->toBe('pending_booking');
    app(FulfillmentService::class)->bookAutomatically($shipment->id, 1);

    expect($shipment->fresh()->status->value)->toBe('booking_failed')
        ->and($shipment->fresh()->last_error)->toBe('Hãng đang bảo trì');
});

it('đơn giữ hàng ở hai kho → hai vận đơn; chỉ trừ tồn khi cả hai đã rời kho', function () {
    $store = I::location(['code' => 'ST-Q1', 'priority' => -1]);
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

it('đặt vận đơn (L31): giữ chỗ rồi gọi hãng ngoài transaction — lần đặt khác trong lúc hãng chưa trả lời bị từ chối, không đặt trùng', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    $concurrent = null;
    FakeApiCarrier::$duringBooking = function () use ($shipment, &$concurrent): void {
        // Lúc hãng đang xử lý: lease đã ghi (đã commit, không giữ khoá dòng) — lần đặt thứ hai bị từ chối ngay.
        expect($shipment->fresh()->booking_lease)->not->toBeNull();
        try {
            app(FulfillmentService::class)->book($shipment->id, null);
        } catch (FulfillmentRejected $exception) {
            $concurrent = $exception->errorCode();
        }
        app(FulfillmentService::class)->bookAutomatically($shipment->id, 1); // job song song: không tính là lỗi
    };

    app(FulfillmentService::class)->book($shipment->id, null);

    expect($concurrent)->toBe('shipment.booking_in_progress')
        ->and(FakeApiCarrier::$booked)->toHaveCount(1)
        ->and($shipment->fresh())->status->value->toBe('created')->booking_lease->toBeNull()->booking_attempts->toBe(0);
});

it('đặt vận đơn (L31): hãng lỗi → trả chỗ ngay; chỗ giữ quá hạn (worker chết giữa chừng) → đặt lại được; huỷ trong lúc gọi hãng → không ghi đè', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    $service = app(FulfillmentService::class);

    FakeApiCarrier::$failBooking = true;
    expect(fn () => $service->book($shipment->id, null))->toThrow(RuntimeException::class, 'Hãng đang bảo trì')
        ->and($shipment->fresh()->booking_lease)->toBeNull();
    FakeApiCarrier::$failBooking = false;

    $shipment->update(['booking_started_at' => now()->subSeconds(30), 'booking_lease' => 'LEASE-CUA-WORKER-KHAC']);
    expect(fn () => $service->book($shipment->id, null))->toThrow(FulfillmentRejected::class);
    $shipment->update(['booking_started_at' => now()->subMinutes(3)]);
    $service->book($shipment->id, null);
    expect($shipment->fresh()->status->value)->toBe('created');

    ($this->place)();
    $second = Shipment::query()->withoutGlobalScopes()->latest('id')->first();
    FakeApiCarrier::$duringBooking = fn () => $service->cancel($second->id, 'khách huỷ qua điện thoại');
    expect(fn () => $service->book($second->id, null))->toThrow(FulfillmentRejected::class)
        ->and($second->fresh())->status->value->toBe('cancelled')->tracking_number->toBeNull()->booking_lease->toBeNull();
});

it('Admin: quyền, mã vận đơn trùng', function () {
    ($this->place)();
    ($this->place)();
    [$first, $second] = Shipment::query()->withoutGlobalScopes()->orderBy('id')->get()->all();

    $this->actingAs(T::staff(['admin.access', 'fulfillment.view']), 'staff');
    $this->get($this->ship)->assertOk();
    $this->post("{$this->ship}/{$first->id}/book", ['tracking_number' => 'X1'])->assertForbidden();

    ($this->asStaff)();
    $this->post("{$this->ship}/{$first->id}/book", ['tracking_number' => 'X1'])->assertSessionHasNoErrors();
    $this->post("{$this->ship}/{$second->id}/book", ['tracking_number' => 'X1'])->assertSessionHasErrors('business');

});

it('vận đơn dùng carrier của phương thức giao khách chọn (dịch vụ = mã phương thức); phí cố định hoặc carrier tắt → mặc định', function () {
    app(Extensions::class)->tag([FakeApiRates::class], ShippingRateProvider::TAG);
    app(Settings::class)->set('core', 'fulfillment.booking', 'on_create');
    $placeWith = function (string $method, int $expected, string $key) {
        $created = $this->postJson("{$this->api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("{$this->api}/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("{$this->api}/checkout/{$created->json('data.id')}/orders", C::orderPayload(['shipping_method' => $method, 'expected_total' => $expected]), [...$headers, 'Idempotency-Key' => $key])->assertCreated();

        return Shipment::query()->withoutGlobalScopes()->where('order_id', ($this->order)()->id)->sole();
    };

    $viaCarrier = $placeWith('fake_express', 345_000, 'carrier-by-source-1');
    expect($viaCarrier->carrier_code)->toBe('fake_api')->and($viaCarrier->service_code)->toBe('fake_express')
        ->and(FakeApiCarrier::$booked)->not->toBeEmpty();

    $flat = $placeWith('standard', 330_000, 'carrier-by-source-2');
    expect($flat->carrier_code)->toBe('manual')->and($flat->service_code)->toBeNull();

    // Đơn đặt với carrier đã tắt sau đó: không còn carrier đó → mặc định thay vì lỗi.
    DB::table('orders')->where('id', ($this->order)()->id)->update(['shipping_method' => json_encode(['code' => 'x', 'source' => 'hang_da_tat'])]);
    DB::table('shipments')->where('order_id', ($this->order)()->id)->update(['status' => 'cancelled']);
    T::seed(fn () => app(FulfillmentService::class)->createForOrder(($this->order)()->id));
    expect(Shipment::query()->withoutGlobalScopes()->where('order_id', ($this->order)()->id)->where('status', '!=', 'cancelled')->sole()->carrier_code)->toBe('manual');
});

it('sự cố giao hàng: đặt vận đơn lỗi và giao không thành công đưa đơn vào hàng đợi "Sự cố giao hàng"; xử lý xong thì ra', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    FakeApiCarrier::$failBooking = true;
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    app(FulfillmentService::class)->bookAutomatically($shipment->id, 1);
    ($this->asStaff)();

    $issueQueue = fn () => $this->get('/admin/orders/orders?stage=issue')->assertOk();
    expect(($this->order)()->delivery_issue)->toBe('booking_failed');
    $issueQueue()->assertInertia(fn (Assert $page) => $page->where('stageCounts.issue', 1)->has('orders', 1)->where('orders.0.delivery_issue', 'booking_failed'));

    // Hãng hoạt động lại, nhân viên đặt lại vận đơn → hết sự cố, đơn về "Chờ đóng gói".
    FakeApiCarrier::$failBooking = false;
    $this->post("{$this->ship}/{$shipment->id}/book", ['tracking_number' => 'TAY1'])->assertSessionHasNoErrors();
    expect(($this->order)()->delivery_issue)->toBeNull();
    $issueQueue()->assertInertia(fn (Assert $page) => $page->where('stageCounts.issue', 0)->where('stageCounts.to_ship', 1));

    foreach (['picked_up', 'out_for_delivery', 'failed_attempt'] as $status) {
        $this->post("{$this->ship}/{$shipment->id}/status", ['status' => $status])->assertSessionHasNoErrors();
    }
    expect(($this->order)()->delivery_issue)->toBe('failed_attempt');
    $issueQueue()->assertInertia(fn (Assert $page) => $page->where('stageCounts.issue', 1)->where('orders.0.delivery_issue', 'failed_attempt'));

    // Hãng giao lại thành công → hết sự cố; lịch sử đơn có vết mở và đóng sự cố.
    foreach (['out_for_delivery', 'delivered'] as $status) {
        $this->post("{$this->ship}/{$shipment->id}/status", ['status' => $status])->assertSessionHasNoErrors();
    }
    expect(($this->order)()->delivery_issue)->toBeNull()
        ->and(DB::table('order_events')->where('type', 'delivery_issue_changed')->orderBy('id')->pluck('to_status')->all())->toBe(['booking_failed', null, 'failed_attempt', null]);
    $issueQueue()->assertInertia(fn (Assert $page) => $page->where('stageCounts.issue', 0));
});

it('hàng đang hoàn về và đã hoàn về đều nằm ở hàng đợi "Sự cố giao hàng" với nhãn riêng', function () {
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    ($this->asStaff)();
    $this->post("{$this->ship}/{$shipment->id}/book", ['tracking_number' => 'GHN2'])->assertSessionHasNoErrors();
    foreach (['picked_up', 'returning'] as $status) {
        $this->post("{$this->ship}/{$shipment->id}/status", ['status' => $status])->assertSessionHasNoErrors();
    }
    $this->get('/admin/orders/orders?stage=issue')->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.delivery_issue', 'returning'));

    $this->post("{$this->ship}/{$shipment->id}/status", ['status' => 'returned'])->assertSessionHasNoErrors();
    expect(($this->order)()->delivery_issue)->toBeNull();
    $this->get('/admin/orders/orders?stage=issue')->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.delivery_issue', 'returned'));
});

it('hook vani.fulfillment.before_push (EP v3 X2): chặn đẩy lên hãng ở nút, hàng loạt, on_create và nhập mã tay; vận đơn giữ "Chờ đóng gói"', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    app(Settings::class)->set('core', 'fulfillment.booking', 'on_create');
    Hook::onValidate('vani.fulfillment.before_push', fn ($data): array => ['Thiếu khối lượng kiện']);
    ($this->place)();
    $api = Shipment::query()->withoutGlobalScopes()->sole();
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'manual');
    ($this->place)();
    $manual = Shipment::query()->withoutGlobalScopes()->whereKeyNot($api->id)->sole();
    ($this->asStaff)();

    $this->post("{$this->ship}/{$api->id}/push")->assertSessionHasErrors(['business' => 'Chưa đẩy lên hãng được: Thiếu khối lượng kiện']);
    $this->post("{$this->ship}/bulk-push", ['ids' => [$api->order_id]])->assertSessionHas('success', fn (string $m) => str_contains($m, 'Đã đẩy 0'))->assertSessionHasErrors('business');
    $this->post("{$this->ship}/{$manual->id}/book", ['tracking_number' => 'TAY1'])->assertSessionHasErrors('business');

    expect($api->fresh()->status->value)->toBe('pending_booking')->and($manual->fresh()->status->value)->toBe('pending_booking')
        ->and(FakeApiCarrier::$booked)->toBe([]);
});

it('ReturnPickup (EP v3 X3): yêu cầu đã duyệt của đơn giao bằng hãng hỗ trợ → "Gọi hãng lấy hàng trả" → Đang gửi về với mã của hãng', function () {
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    app(Settings::class)->set('core', 'fulfillment.booking', 'on_create');
    ($this->place)();
    $order = ($this->order)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    $this->postJson('/api/shipping/fake_api/webhook', FakeApiCarrier::webhook((string) $shipment->tracking_number, 'delivered', 'E1'))->assertOk();
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'returns.view', 'returns.manage']), 'staff');
    $this->post('/admin/returns/returns', ['order_id' => $order->id, 'lines' => [$order->lines()->value('id') => 1], 'reason_code' => 'wrong_size'])->assertRedirect();
    $return = ReturnRequest::query()->sole();
    $this->post("/admin/returns/returns/{$return->id}/transition", ['to' => 'approved', 'lock_version' => $return->lock_version])->assertSessionHasNoErrors();

    $this->get("/admin/returns/returns/{$return->id}")->assertInertia(fn (Assert $page) => $page->where('pickupCarrier', 'Hãng giả'));
    $this->post("/admin/returns/returns/{$return->id}/pickup")->assertSessionHas('success', 'Đã gọi hãng đến lấy hàng trả — mã vận đơn FKR000001.');

    expect($return->fresh()->status->value)->toBe('in_transit')->and($return->fresh()->return_tracking_number)->toBe('FKR000001')
        ->and(FakeApiCarrier::$returnPickups[0]->orderNumber)->toBe($order->number);
    $this->post("/admin/returns/returns/{$return->id}/pickup")->assertSessionHasErrors('business');
});
