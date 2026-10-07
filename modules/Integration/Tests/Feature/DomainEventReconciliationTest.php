<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Integration\Persistence\Models\IntegrationEventRecord;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Tests\Support\JsonSchemaSubset;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Phase 4: đối soát event tích hợp ngoài order.* — dựng lại payment.*, return.*, shipment.*, order.lines_cancelled từ
| trạng thái nghiệp vụ (contract đọc), không tạo giao dịch mới, không phát trùng.
*/

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    FakeOnlineGateway::$refunds = [];
    ['s' => $s] = C::store();
    $system = fn (Closure $callback) => app(CurrentContext::class)->runAs(ContextScope::system('test'), $callback);
    $place = function (string $method, int $quantity) use ($s) {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $s->id, 'quantity' => $quantity], $headers)->assertOk();
        $placed = $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 300_000 * $quantity + ($quantity === 1 ? 30_000 : 0)]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();

        return [Order::query()->withoutGlobalScopes()->latest('id')->first(), ['X-Vani-Order-Token' => $placed->json('data.access_token')], $placed->json('data.payment')];
    };

    // Đơn COD: huỷ một phần → giao (thu COD) → đổi trả hoàn tất.
    [$this->cod, $headers] = $place('cod', 3);
    $system(fn () => app(OrderCommands::class)->cancelLines($this->cod->id, [$this->cod->lines()->value('id') => 1], 'hết hàng', $this->cod->fresh()->lock_version));
    $this->shipment = Shipment::query()->where('status', '!=', 'cancelled')->sole();
    $system(function () {
        $service = app(FulfillmentService::class);
        $service->book($this->shipment->id, 'T1');
        $service->updateStatus($this->shipment->id, ShipmentStatus::PickedUp, 'e1', 'staff');
        $service->updateStatus($this->shipment->id, ShipmentStatus::Delivered, 'e2', 'staff');
    });
    $this->postJson("/api/storefront/v1/orders/{$this->cod->public_id}/returns", ['lines' => [['order_line_id' => $this->cod->lines()->value('id'), 'quantity' => 1]], 'reason_code' => 'wrong_size'], $headers)->assertCreated();
    $return = ReturnRequest::query()->sole();
    $this->actingAs(T::staff(['admin.access', 'returns.view', 'returns.manage', 'returns.refund']), 'staff');
    $this->post("/admin/returns/returns/{$return->id}/transition", ['to' => 'approved', 'lock_version' => 0])->assertSessionHasNoErrors();
    $this->post("/admin/returns/returns/{$return->id}/receive", ['conditions' => []])->assertSessionHasNoErrors();
    $this->post("/admin/returns/returns/{$return->id}/resolve", ['amount' => 300_000])->assertSessionHasNoErrors();

    // Đơn online: thu tiền rồi huỷ → hoàn tự động.
    [$online, , $payment] = $place('fake_online', 1);
    $this->postJson('/api/payments/fake_online/callback', FakeOnlineGateway::callbackPayload($payment['id'], 'TXN-REC-1', 'paid', 330_000))->assertOk();
    $system(fn () => app(OrderCommands::class)->cancel($online->id, 'hết hàng', 'staff'));

    $this->business = fn (): array => array_map(fn (string $table): int => DB::table($table)->count(), ['payments', 'payment_transactions', 'refunds', 'return_requests', 'shipments', 'order_events', 'stock_movements']);
    $this->related = ['order.lines_cancelled', 'payment.captured', 'payment.refunded', 'return.created', 'return.resolved', 'shipment.status_changed'];
});

it('bù đúng một lần mọi event payment/return/shipment/lines_cancelled bị mất, payload khớp schema, không tạo giao dịch mới', function () {
    $original = IntegrationEventRecord::query()->whereIn('event_type', $this->related)->get()
        ->map(fn ($record) => [$record->event_type, collect($record->payload)->except('order')->all()])->all();
    $deleted = DB::table('integration_events')->whereIn('event_type', $this->related)->delete();
    $before = ($this->business)();

    $this->artisan('vani:integration:reconcile-orders', ['--grace' => 0])->assertSuccessful();

    $repaired = IntegrationEventRecord::query()->whereIn('event_type', $this->related)->get();
    expect(($this->business)())->toBe($before) // không chạm trạng thái nghiệp vụ
        ->and($repaired->every(fn ($record) => ($record->payload['reconciled'] ?? null) === true))->toBeTrue()
        ->and($repaired->pluck('event_type')->unique()->values()->all())->toEqualCanonicalizing($this->related);

    // Mỗi thực thể đúng một event; trùng nội dung với bản gốc (trừ ảnh chụp đơn và cờ reconciled, và chuỗi
    // shipment chỉ còn trạng thái hiện tại).
    $rebuilt = $repaired->map(fn ($record) => [$record->event_type, collect($record->payload)->except(['order', 'reconciled'])->all()])->all();
    foreach ($rebuilt as [$type, $data]) {
        if ($type === 'shipment.status_changed' && $data['to'] === 'delivered') {
            // Vận đơn đã giao: bên nhận mất cả picked_up lẫn delivered → chuyển từ trạng thái đầu sang trạng thái hiện tại.
            expect([$data['from'], $data['to']])->toBe(['pending_booking', 'delivered']);

            continue;
        }
        expect($original)->toContain([$type, $data]);
    }
    expect(count($rebuilt))->toBe($deleted - 2); // vận đơn đã giao có 3 event gốc (created, picked_up, delivered); bù chỉ trạng thái hiện tại

    foreach ($repaired as $record) {
        $envelope = json_decode(json_encode($record->envelope()), true);
        expect([...JsonSchemaSubset::validate($envelope, base_path('docs/api/schemas/envelope.v1.json')), ...JsonSchemaSubset::validate($envelope['data'], base_path("docs/api/schemas/events/{$record->event_type}.json"))])->toBe([]);
    }

    // Chạy lại: không còn chênh lệch, không phát trùng.
    $this->artisan('vani:integration:reconcile-orders', ['--grace' => 0])->assertSuccessful();
    expect(IntegrationEventRecord::query()->whereIn('event_type', $this->related)->count())->toBe($repaired->count())
        ->and((int) DB::table('integration_reconciliations')->latest('id')->value('discrepancies'))->toBe(0);
});

it('vận đơn: event bù lấy from = trạng thái bên nhận biết gần nhất', function () {
    DB::table('integration_events')->where('event_type', 'shipment.status_changed')->where('payload->to', 'delivered')->delete();

    $this->artisan('vani:integration:reconcile-orders', ['--grace' => 0])->assertSuccessful();

    $event = IntegrationEventRecord::query()->where('event_type', 'shipment.status_changed')->latest('id')->first();
    expect([$event->payload['from'], $event->payload['to'], $event->payload['shipment_id'], $event->payload['reconciled']])->toBe(['picked_up', 'delivered', $this->shipment->public_id, true]);
});

it('--dry-run chỉ báo cáo chênh lệch theo thực thể', function () {
    DB::table('integration_events')->where('event_type', 'payment.refunded')->delete();

    $this->artisan('vani:integration:reconcile-orders', ['--grace' => 0, '--dry-run' => true])->assertSuccessful();

    $report = DB::table('integration_reconciliations')->latest('id')->first();
    $details = json_decode((string) $report->details, true);
    expect([(int) $report->discrepancies, (int) $report->repaired])->toBe([1, 0])
        ->and($details[0]['missing'])->toBe('payment.refunded')
        ->and($details[0]['entity'])->toHaveKey('refund_id')
        ->and(IntegrationEventRecord::query()->where('event_type', 'payment.refunded')->exists())->toBeFalse();
});
