<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Persistence\Models\IntegrationEventRecord;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Tests\Feature\Fixtures\FakeErpConnector;
use Modules\Integration\Tests\Feature\IntegrationTestHelpers as H;
use Modules\Ordering\Events\OrderConfirmed;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Events\PaymentCaptured;

require_once __DIR__.'/IntegrationTestHelpers.php';

beforeEach(function () {
    FakeErpConnector::reset();
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->placeOrder = function (): string {
        $api = '/api/storefront/v1';
        $headers = [];
        $created = $this->postJson("{$api}/carts", [], $headers)->assertCreated();
        $headers['X-Vani-Cart-Token'] = $created->json('meta.token');
        $this->postJson("{$api}/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->postJson("{$api}/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'integration-order-1'])
            ->assertCreated()->json('data.number');
    };
});

it('đặt đơn COD → event feed order.created + order.confirmed theo thứ tự, payload canonical', function () {
    $number = ($this->placeOrder)();

    $events = IntegrationEventRecord::query()->orderBy('id')->get();
    expect($events->pluck('event_type')->all())->toBe(['order.created', 'order.confirmed'])
        ->and($events->pluck('aggregate_id')->unique()->all())->toBe([$number])
        ->and($events[0]->correlation_id)->not->toBeNull();

    $order = $events[0]->payload['order'];
    expect($order['schema'])->toBe('vanishop.order.v1')
        ->and($order['number'])->toBe($number)
        ->and($order['total_amount'])->toBe(330_000)
        ->and($order['lines'][0]['sku'])->toBe($this->s->sku)
        ->and($order['lines'][0]['quantity'])->toBe(1)
        ->and($order['lines'][0]['brand'])->toBe($this->brand->name)
        ->and($order['source'])->toBe('web')
        ->and($events[1]->payload['reason'])->toBe('cod_auto_confirm');
});

it('fan-out theo subscription (event type, trạng thái) và connector hỗ trợ', function () {
    H::client('erp-main');
    H::client('odo');
    [$all] = H::subscription('erp-main', ['order.*']);
    [$paused] = H::subscription('odo', ['*']);
    $paused->update(['status' => 'paused']);                          // tạm dừng → không nhận
    [$payments] = H::subscription('erp-main', ['payment.captured']); // không khớp loại
    app(Extensions::class)->tag([FakeErpConnector::class], Connector::TAG);

    ($this->placeOrder)();

    $targets = OutboxRecord::query()->orderBy('id')->get()->map(fn (OutboxRecord $row): string => "{$row->message_type}>{$row->target}")->all();
    expect($targets)->toBe([
        "order.created>{$all->target()}", 'order.created>fake-erp',
        "order.confirmed>{$all->target()}", 'order.confirmed>fake-erp',
    ])->and(OutboxRecord::query()->where('target', $payments->target())->exists())->toBeFalse();

    $message = OutboxRecord::query()->first();
    expect($message->payload['event_type'])->toBe('order.created')
        ->and($message->payload['source'])->toBe('vanishop')
        ->and($message->payload['data']['order']['number'])->toBe($message->aggregate_id)
        ->and($message->status->value)->toBe('pending');
});

it('transaction nghiệp vụ rollback → không có event, không có message', function () {
    H::client('erp-main');
    H::subscription('erp-main');

    try {
        DB::transaction(function () {
            OrderConfirmed::dispatch(1, 'X', 'test');
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(IntegrationEventRecord::query()->count())->toBe(0)
        ->and(OutboxRecord::query()->count())->toBe(0);
});

it('event liên quan (thanh toán) mang số đơn làm aggregate, giữ thứ tự sau order.*', function () {
    $number = ($this->placeOrder)();
    $orderId = T::seed(fn () => Order::query()->where('number', $number)->value('id'));

    PaymentCaptured::dispatch(99, $orderId, 330_000, 'cod', '01JPAYMENTPUBLIC0000000099');

    $event = IntegrationEventRecord::query()->where('event_type', 'payment.captured')->sole();
    expect($event->aggregate_id)->toBe($number)
        ->and($event->payload)->toEqual(['order_number' => $number, 'payment_id' => '01JPAYMENTPUBLIC0000000099', 'gateway' => 'cod', 'amount' => 330_000, 'currency' => 'VND'])
        ->and($event->id)->toBeGreaterThan(IntegrationEventRecord::query()->where('event_type', 'order.created')->value('id'));
});
