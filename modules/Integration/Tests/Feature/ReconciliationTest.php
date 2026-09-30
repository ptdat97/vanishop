<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Integration\Persistence\Models\IntegrationEventRecord;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/IntegrationTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->placeOrder = function (string $key): string {
        $headers = ['X-Vani-Channel' => 'web-lumiere'];
        $created = $this->postJson('/api/storefront/v1/carts', [], $headers)->assertCreated();
        $headers['X-Vani-Cart-Token'] = $created->json('meta.token');
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => $key])
            ->assertCreated()->json('data.number');
    };
});

it('bù event bị mất (tiến trình chết giữa commit và ghi feed), đánh dấu reconciled; chạy lại không bù trùng', function () {
    $complete = ($this->placeOrder)('reconcile-order-1');
    $lost = ($this->placeOrder)('reconcile-order-2');
    // Giả lập mất event: feed của đơn thứ hai không có gì.
    IntegrationEventRecord::query()->where('aggregate_id', $lost)->delete();
    DB::table('integration_outbox')->where('aggregate_id', $lost)->delete();

    $this->travel(10)->minutes();
    $this->artisan('vani:integration:reconcile-orders', ['--dry-run' => true])->expectsOutputToContain('2 đơn, 2 thiếu, 0 đã bù')->assertSuccessful();
    expect(IntegrationEventRecord::query()->where('aggregate_id', $lost)->count())->toBe(0);

    $this->artisan('vani:integration:reconcile-orders')->expectsOutputToContain('2 đơn, 2 thiếu, 2 đã bù')->assertSuccessful();
    $events = IntegrationEventRecord::query()->where('aggregate_id', $lost)->orderBy('id')->get();
    expect($events->pluck('event_type')->all())->toBe(['order.created', 'order.confirmed'])
        ->and($events[0]->payload['reconciled'])->toBeTrue()
        ->and($events[0]->payload['order']['number'])->toBe($lost)
        ->and(IntegrationEventRecord::query()->where('aggregate_id', $complete)->count())->toBe(2);

    $this->artisan('vani:integration:reconcile-orders')->expectsOutputToContain('2 đơn, 0 thiếu, 0 đã bù')->assertSuccessful();
    $report = DB::table('integration_reconciliations')->orderBy('id')->get();
    expect($report)->toHaveCount(3)
        ->and(json_decode($report[1]->details, true))->toBe([['order' => $lost, 'missing' => 'order.created'], ['order' => $lost, 'missing' => 'order.confirmed']]);
});

it('đơn đã huỷ phải có order.cancelled; đơn vừa đổi trong khoảng grace chưa bị đối soát', function () {
    $number = ($this->placeOrder)('reconcile-order-3');
    $orderId = T::seed(fn () => Order::query()->where('number', $number)->value('id'));
    T::seed(fn () => app(OrderTransitions::class)->transition($orderId, OrderStatus::Cancelled, 'customer_request', 'staff'));
    IntegrationEventRecord::query()->where('event_type', 'order.cancelled')->delete();

    $this->artisan('vani:integration:reconcile-orders')->expectsOutputToContain('0 đơn')->assertSuccessful();

    $this->travel(10)->minutes();
    $this->artisan('vani:integration:reconcile-orders')->expectsOutputToContain('1 đơn, 1 thiếu, 1 đã bù')->assertSuccessful();
    expect(IntegrationEventRecord::query()->where('event_type', 'order.cancelled')->sole()->payload['order']['status'])->toBe('cancelled');
});
