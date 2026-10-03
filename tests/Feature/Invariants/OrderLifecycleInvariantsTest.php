<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Bất biến xuyên Ordering ↔ Inventory ↔ Fulfillment ↔ Returns ↔ Payment ↔ Integration, kiểm tra cuối mỗi vòng đời đơn:
|   I1 reserved của mỗi stock_level = tổng hàng giữ đang active; on_hand, reserved ≥ 0.
|   I2 thay đổi on_hand/reserved kể từ mốc = tổng stock_movements (sổ biến động đầy đủ, không sửa tồn ngoài sổ).
|   I3 đơn đã huỷ hoặc hàng đã rời kho → không còn hàng giữ active của đơn.
|   I4 tiền hoàn đã xong ≤ tiền đã thu của mỗi payment.
|   I5 event tích hợp: mọi đơn có order.created; đơn huỷ có order.cancelled; đơn đã thu tiền có payment.captured.
*/

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    FakeOnlineGateway::$refunds = [];
    ['s' => $this->s, 'location' => $this->location] = C::store();
    $this->api = '/api/storefront/v1';

    $this->place = function (int $quantity = 1, string $method = 'cod') {
        $created = $this->postJson("{$this->api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => $quantity], $headers)->assertOk();
        $placed = $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 300_000 * $quantity + ($quantity === 1 ? 30_000 : 0)]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();

        return [Order::query()->withoutGlobalScopes()->latest('id')->first(), ['X-Vani-Order-Token' => $placed->json('data.access_token')], $placed->json('data.payment')];
    };
    $this->system = fn (Closure $callback) => app(CurrentContext::class)->runAs(ContextScope::system('test'), $callback);
    $this->ship = function (Shipment $shipment, ShipmentStatus ...$statuses): void {
        ($this->system)(function () use ($shipment, $statuses) {
            $service = app(FulfillmentService::class);
            if ($shipment->fresh()->tracking_number === null) {
                $service->book($shipment->id, 'T'.$shipment->id);
            }
            foreach ($statuses as $status) {
                $service->updateStatus($shipment->id, $status, "test:{$shipment->id}:{$status->value}", 'staff', strict: true);
            }
        });
    };
    $this->onHand = fn ($location = null): int => (int) DB::table('stock_levels')->where('variant_id', $this->s->id)->where('location_id', ($location ?? $this->location)->id)->value('on_hand');

    // Mốc của I2: tồn sau khi dựng dữ liệu (dữ liệu test ghi thẳng, không qua sổ).
    $this->baseline = null;
    $this->snapshot = function () {
        $this->baseline = DB::table('stock_levels')->get(['location_id', 'variant_id', 'on_hand', 'reserved'])
            ->keyBy(fn ($row) => "{$row->location_id}:{$row->variant_id}")->all();
        $this->movementsFrom = (int) DB::table('stock_movements')->max('id');
    };
    $this->assertInvariants = function () {
        foreach (DB::table('stock_levels')->get() as $level) {
            $key = "{$level->location_id}:{$level->variant_id}";
            $active = (int) DB::table('stock_reservations')->where(['location_id' => $level->location_id, 'variant_id' => $level->variant_id, 'status' => 'active'])->sum('quantity');
            $moves = DB::table('stock_movements')->where('id', '>', $this->movementsFrom)->where(['location_id' => $level->location_id, 'variant_id' => $level->variant_id])
                ->selectRaw('coalesce(sum(on_hand_delta), 0) as on_hand, coalesce(sum(reserved_delta), 0) as reserved')->first();
            $base = $this->baseline[$key] ?? (object) ['on_hand' => 0, 'reserved' => 0];

            expect((int) $level->reserved)->toBe($active, "I1 reserved [{$key}]")
                ->and((int) $level->on_hand)->toBeGreaterThanOrEqual(0)
                ->and((int) $level->reserved)->toBeGreaterThanOrEqual(0)
                ->and((int) $level->on_hand - (int) $base->on_hand)->toBe((int) $moves->on_hand, "I2 on_hand [{$key}]")
                ->and((int) $level->reserved - (int) $base->reserved)->toBe((int) $moves->reserved, "I2 reserved [{$key}]");
        }

        foreach (Order::query()->withoutGlobalScopes()->get() as $order) {
            $terminal = $order->order_status->value === 'cancelled' || in_array($order->fulfillment_status, ['shipped', 'delivered', 'returned_to_sender'], true);
            if ($terminal) {
                expect(DB::table('stock_reservations')->where('reservation_key', $order->reservation_key)->where('status', 'active')->count())->toBe(0, "I3 đơn {$order->number}");
            }

            $events = DB::table('integration_events')->where('aggregate_id', $order->number)->pluck('event_type')->all();
            expect($events)->toContain('order.created');
            if ($order->order_status->value === 'cancelled') {
                expect($events)->toContain('order.cancelled');
            }
            if (DB::table('payments')->where('order_id', $order->id)->whereIn('status', ['paid', 'partially_refunded', 'refunded'])->exists()) {
                expect($events)->toContain('payment.captured');
            }
        }

        foreach (DB::table('payments')->get() as $payment) {
            $captured = in_array($payment->status, ['paid', 'partially_refunded', 'refunded'], true) ? (int) $payment->amount : 0;
            expect((int) DB::table('refunds')->where('payment_id', $payment->id)->where('status', 'completed')->sum('amount'))->toBeLessThanOrEqual($captured, "I4 payment {$payment->public_id}");
        }
    };
    ($this->snapshot)();
});

it('huỷ trước khi giao: trả lại hàng giữ, tồn nguyên vẹn', function () {
    [$order] = ($this->place)();
    ($this->system)(fn () => app(OrderCommands::class)->cancel($order->id, 'khách đổi ý', 'staff'));

    expect($order->fresh()->order_status->value)->toBe('cancelled')
        ->and(($this->onHand)())->toBe(10)
        ->and(Shipment::query()->sole()->status->value)->toBe('cancelled');
    ($this->assertInvariants)();
});

it('giao thành công: trừ đúng 1 khi rời kho, không trừ lần hai khi giao', function () {
    [$order] = ($this->place)();
    $shipment = Shipment::query()->sole();
    ($this->ship)($shipment, ShipmentStatus::PickedUp);
    expect(($this->onHand)())->toBe(9);
    ($this->ship)($shipment, ShipmentStatus::InTransit, ShipmentStatus::Delivered);

    expect(($this->onHand)())->toBe(9)->and($order->fresh()->payment_status)->toBe('cod_collected');
    ($this->assertInvariants)();
});

it('hàng hoàn về kho rồi huỷ đơn: nhập lại đúng 1 lần, COD không thu', function () {
    [$order] = ($this->place)();
    $shipment = Shipment::query()->sole();
    ($this->ship)($shipment, ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::Returned);
    ($this->system)(fn () => app(OrderCommands::class)->cancel($order->id, 'hoàn hàng', 'staff'));
    ($this->ship)($shipment); // gọi lại không đổi gì

    expect(($this->onHand)())->toBe(10)
        ->and($order->fresh()->order_status->value)->toBe('cancelled')
        ->and(DB::table('payments')->value('status'))->not->toBe('paid');
    ($this->assertInvariants)();
});

it('đổi trả: nhận lại hàng bán được thì nhập kho, hỏng thì không; tiền hoàn ≤ đã thu', function (string $condition, int $expectedOnHand) {
    [$order, $headers] = ($this->place)(2);
    ($this->ship)(Shipment::query()->sole(), ShipmentStatus::PickedUp, ShipmentStatus::Delivered);
    $this->postJson("{$this->api}/orders/{$order->public_id}/returns", ['lines' => [['order_line_id' => $order->lines()->value('id'), 'quantity' => 1]], 'reason_code' => 'wrong_size'], $headers)->assertCreated();
    $return = ReturnRequest::query()->sole();

    $this->actingAs(T::staff(['admin.access', 'returns.view', 'returns.manage', 'returns.refund']), 'staff');
    $this->post("/admin/returns/returns/{$return->id}/transition", ['to' => 'approved', 'lock_version' => $return->lock_version])->assertSessionHasNoErrors();
    // Khoá tình trạng là id dòng trả hàng; khoá không thuộc yêu cầu bị từ chối (không âm thầm nhập kho như "bán được").
    $returnLineId = $return->lines()->value('id');
    $this->post("/admin/returns/returns/{$return->id}/receive", ['conditions' => [$returnLineId + 999 => $condition]])->assertSessionHasErrors('business');
    $this->post("/admin/returns/returns/{$return->id}/receive", ['conditions' => [$returnLineId => $condition]])->assertSessionHasNoErrors();
    $this->post("/admin/returns/returns/{$return->id}/resolve", ['amount' => 300_000])->assertSessionHasNoErrors();

    expect(($this->onHand)())->toBe($expectedOnHand)->and($return->fresh()->status->value)->toBe('resolved');
    ($this->assertInvariants)();
})->with([
    'bán được' => ['sellable', 9],
    'hỏng' => ['damaged', 8],
]);

it('tách hai kho: kho A hoàn về trước khi kho B rời kho — tồn từng kho đúng, không âm', function () {
    $second = I::location(['priority' => -1]);
    I::stock($this->location, $this->s->id, 1);
    I::stock($second, $this->s->id, 1);
    ($this->snapshot)();

    [$order] = ($this->place)(2);
    [$a, $b] = Shipment::query()->orderBy('id')->get()->all();
    expect([$a->location_id, $b->location_id])->toEqualCanonicalizing([$this->location->id, $second->id]);

    ($this->ship)($a, ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::Returned);
    ($this->assertInvariants)();
    expect(fn () => ($this->system)(fn () => app(OrderCommands::class)->cancel($order->id, 'x', 'staff')))->toThrow(OrderActionRejected::class);

    ($this->ship)($b, ShipmentStatus::PickedUp, ShipmentStatus::Delivered);
    $levelA = fn () => DB::table('stock_levels')->where('location_id', $a->location_id)->where('variant_id', $this->s->id)->first();
    $levelB = fn () => DB::table('stock_levels')->where('location_id', $b->location_id)->where('variant_id', $this->s->id)->first();
    expect([(int) $levelA()->on_hand, (int) $levelA()->reserved, (int) $levelB()->on_hand, (int) $levelB()->reserved])->toBe([1, 0, 0, 0]);
    ($this->assertInvariants)();
});

it('thanh toán online xong rồi huỷ trước khi giao: trả hàng giữ, hoàn đúng số đã thu', function () {
    [$order, , $payment] = ($this->place)(1, 'fake_online');
    $this->postJson('/api/payments/fake_online/callback', FakeOnlineGateway::callbackPayload($payment['id'], 'TXN-INV-1', 'paid', 330_000))->assertOk();
    expect($order->fresh()->payment_status)->toBe('paid');

    ($this->system)(fn () => app(OrderCommands::class)->cancel($order->id, 'hết hàng', 'staff'));

    expect(($this->onHand)())->toBe(10)
        ->and((int) DB::table('refunds')->sum('amount'))->toBe(330_000);
    ($this->assertInvariants)();
});

it('thanh toán online hết hạn: đơn huỷ, hàng giữ được trả', function () {
    [$order] = ($this->place)(1, 'fake_online');
    $this->travel(2)->hours();
    $this->artisan('vani:payment:expire')->assertSuccessful();

    expect($order->fresh()->order_status->value)->toBe('cancelled')->and(($this->onHand)())->toBe(10);
    ($this->assertInvariants)();
});
