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
| Ranh giới tích hợp: event Core phát ra (envelope + data) khớp JSON Schema công bố ở docs/api/schemas. Đổi payload mà
| không cập nhật schema → test đỏ; đổi phá vỡ (xoá/đổi kiểu trường) cần schema v2 + loại event/schema_version mới.
*/

function schemaDir(): string
{
    return base_path('docs/api/schemas');
}

it('mọi loại event trong envelope có schema data riêng, và ngược lại', function () {
    $envelope = json_decode((string) file_get_contents(schemaDir().'/envelope.v1.json'), true);
    $types = $envelope['properties']['event_type']['enum'];
    $files = array_map(fn (string $file): string => basename($file, '.json'), glob(schemaDir().'/events/*.json'));

    expect($files)->toEqualCanonicalizing($types);
});

it('event phát từ các luồng thật (đặt, xác nhận, giao, thu tiền, đổi trả, hoàn tiền, huỷ, đối soát) khớp schema', function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    FakeOnlineGateway::$refunds = [];
    ['s' => $s] = C::store();
    $place = function (string $method, int $quantity = 1) use ($s) {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $s->id, 'quantity' => $quantity], $headers)->assertOk();
        $placed = $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 300_000 * $quantity + ($quantity === 1 ? 30_000 : 0)]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();

        return [Order::query()->withoutGlobalScopes()->latest('id')->first(), ['X-Vani-Order-Token' => $placed->json('data.access_token')], $placed->json('data.payment')];
    };
    $system = fn (Closure $callback) => app(CurrentContext::class)->runAs(ContextScope::system('test'), $callback);

    // COD: tạo → xác nhận → vận đơn → giao (thu COD) → đổi trả → hoàn tất.
    [$cod, $headers] = $place('cod', 2);
    $shipment = Shipment::query()->sole();
    $system(function () use ($shipment) {
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'T1');
        $service->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'e1', 'staff');
        $service->updateStatus($shipment->id, ShipmentStatus::Delivered, 'e2', 'staff');
    });
    $this->postJson("/api/storefront/v1/orders/{$cod->public_id}/returns", ['lines' => [['order_line_id' => $cod->lines()->value('id'), 'quantity' => 1]], 'reason_code' => 'wrong_size'], $headers)->assertCreated();
    $return = ReturnRequest::query()->sole();
    $this->actingAs(T::staff(['admin.access', 'returns.view', 'returns.manage', 'returns.refund']), 'staff');
    $this->post("/admin/returns/returns/{$return->id}/transition", ['to' => 'approved', 'lock_version' => 0])->assertSessionHasNoErrors();
    $this->post("/admin/returns/returns/{$return->id}/receive", ['conditions' => []])->assertSessionHasNoErrors();
    $this->post("/admin/returns/returns/{$return->id}/resolve", ['amount' => 300_000])->assertSessionHasNoErrors();

    // Online: thu tiền rồi huỷ → hoàn tiền tự động.
    [$online, , $payment] = $place('fake_online');
    $this->postJson('/api/payments/fake_online/callback', FakeOnlineGateway::callbackPayload($payment['id'], 'TXN-SCHEMA-1', 'paid', 330_000))->assertOk();
    $system(fn () => app(OrderCommands::class)->cancel($online->id, 'hết hàng', 'staff'));

    // Đối soát bù event bị mất.
    DB::table('integration_events')->where('aggregate_id', $cod->number)->where('event_type', 'order.confirmed')->delete();
    $this->artisan('vani:integration:reconcile-orders', ['--grace' => 0])->assertSuccessful();

    $records = IntegrationEventRecord::query()->orderBy('id')->get();
    expect($records->pluck('event_type')->unique()->values()->all())->toEqualCanonicalizing([
        'order.created', 'order.confirmed', 'order.cancelled', 'payment.captured', 'payment.refunded', 'return.created', 'return.resolved', 'shipment.status_changed',
    ])->and($records->contains(fn ($record) => ($record->payload['reconciled'] ?? false) === true))->toBeTrue();

    foreach ($records as $record) {
        // Qua JSON như khi gửi đi (webhook/GET /events), không kiểm mảng PHP trực tiếp.
        $envelope = json_decode(json_encode($record->envelope()), true);
        $errors = [
            ...JsonSchemaSubset::validate($envelope, schemaDir().'/envelope.v1.json'),
            ...JsonSchemaSubset::validate($envelope['data'], schemaDir()."/events/{$record->event_type}.json"),
        ];
        expect($errors)->toBe([], "{$record->event_type}: ".implode('; ', $errors));
    }
});

it('bộ kiểm tra schema bắt được payload sai (thiếu trường, sai kiểu, trường thừa, enum)', function () {
    $order = json_decode((string) file_get_contents(schemaDir().'/vanishop.order.v1.json'), true);
    $data = ['order_number' => 'VN1', 'shipment_id' => '5', 'from' => 'picked_up', 'to' => 'teleported', 'extra' => 1];

    expect(JsonSchemaSubset::validate($data, schemaDir().'/events/shipment.status_changed.json'))->toHaveCount(3)
        ->and(JsonSchemaSubset::validate(['order' => ['schema' => 'vanishop.order.v1']], schemaDir().'/events/order.created.json'))->toHaveCount(count($order['required']) - 1);
});
