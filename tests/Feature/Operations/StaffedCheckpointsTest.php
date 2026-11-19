<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Contracts\ReturnPolicy;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Returns\Tests\Feature\Fixtures\AutoApproveWrongSizePolicy;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| ADR-046 (vision §2.2, mục tiêu G9): ba chốt có người — ① xác nhận đơn COD, ② đẩy đơn lên hãng, ③ duyệt đổi trả.
| Cùng một kịch bản chạy hai lần: mặc định (nhân viên bấm ở cả ba chốt, chỉ từ Tổng quan / danh sách / trang đơn) và bật
| tự động cả ba chốt bằng điểm mở rộng (cấu hình + chính sách plugin). Tồn và tiền phải như nhau; chỉ khác nguồn trong
| lịch sử (`staff` ↔ `system`). Bước sau ba chốt (bàn giao, hãng báo giao, nhận hàng trả, chuyển trả tiền) giống nhau.
*/

beforeEach(function () {
    app(Extensions::class)->tag([FakeApiCarrier::class], CarrierRegistry::CARRIERS_TAG);
    app(Extensions::class)->tag([AutoApproveWrongSizePolicy::class], ReturnPolicy::TAG);
    FakeApiCarrier::$failBooking = false;
    FakeApiCarrier::$booked = [];
    app(Settings::class)->set('core', 'fulfillment.default_carrier', 'fake_api');
    ['s' => $this->s, 'location' => $this->location] = C::store(stock: 10);
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', 'fulfillment.view', 'fulfillment.manage', 'returns.view', 'returns.manage', 'returns.refund', 'payments.view', 'payments.refund']), 'staff');

    $this->run = function (bool $automated): array {
        if ($automated) {
            C::autoConfirmCod();
            app(Settings::class)->set('core', 'fulfillment.booking', 'on_create');
            T::seed(fn () => app(Settings::class)->set('core', 'returns.policy', 'test_auto_wrong_size'));
        }
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $cartHeaders = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("{$api}/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $cartHeaders)->assertOk();
        $placed = $this->postJson("{$api}/checkout/{$created->json('data.id')}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => 600_000]), [...$cartHeaders, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();
        $order = Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
        $tasks = fn (): array => collect($this->get('/admin')->assertOk()->viewData('page')['props']['tasks'])->keyBy('key')->all();
        $bulk = fn (string $key): ?array => collect($this->get('/admin/orders/orders')->assertOk()->viewData('page')['props']['extensions']['actions'])->firstWhere('key', $key);

        // ① Xác nhận đơn COD.
        expect($order->order_status->value)->toBe($automated ? 'processing' : 'pending');
        if (! $automated) {
            expect($tasks()['orders.to_confirm']['count'])->toBe(1)->and(Shipment::query()->where('order_id', $order->id)->exists())->toBeFalse();
            $this->post(route('admin.orders.orders.bulk-confirm'), ['ids' => [$order->id]])->assertSessionHasNoErrors();
        }

        // ② Đóng gói, đẩy lên hãng.
        $shipment = Shipment::query()->where('order_id', $order->id)->sole();
        expect($shipment->status->value)->toBe($automated ? 'created' : 'pending_booking');
        if (! $automated) {
            expect($tasks()['orders.to_ship']['count'])->toBe(1)->and(FakeApiCarrier::$booked)->toBe([]);
            $this->post($bulk('push')['url'], ['ids' => [$order->id]])->assertSessionHasNoErrors();
        }
        $tracking = (string) $shipment->fresh()->tracking_number;
        $this->post(route('admin.fulfillment.shipments.bulk-hand-over'), ['ids' => [$order->id]])->assertSessionHasNoErrors();
        $this->postJson('/api/shipping/fake_api/webhook', FakeApiCarrier::webhook($tracking, 'delivered', "E-{$order->id}"))->assertOk();

        // ③ Đổi trả: khách gửi, CSKH duyệt (hoặc chính sách tự duyệt), CSKH nhận hàng và chuyển trả tiền.
        $this->postJson("{$api}/orders/{$order->public_id}/returns", ['lines' => [['order_line_id' => $order->lines()->value('id'), 'quantity' => 1]], 'reason_code' => 'wrong_size'], ['X-Vani-Order-Token' => $placed->json('data.access_token')])->assertCreated();
        $return = ReturnRequest::query()->where('order_id', $order->id)->sole();
        expect($return->status->value)->toBe($automated ? 'approved' : 'requested');
        if (! $automated) {
            expect($tasks()['returns.requested']['count'])->toBe(1);
            $this->post(route('admin.returns.returns.bulk-approve'), ['ids' => [$return->id]])->assertSessionHasNoErrors();
        }
        $this->post(route('admin.returns.returns.receive', $return->id))->assertSessionHasNoErrors();
        $this->post(route('admin.returns.returns.resolve', $return->id), ['amount' => 300_000])->assertSessionHasNoErrors();
        $this->post(route('admin.payment.refunds.complete', DB::table('refunds')->value('id')), ['note' => 'CK trả khách'])->assertSessionHasNoErrors();

        $source = fn (string $table, string $key, int $id, string $to): ?string => DB::table($table)->where($key, $id)->where('to_status', $to)->value('source');

        return [
            'outcome' => [
                'on_hand' => (int) DB::table('stock_levels')->where('variant_id', $this->s->id)->where('location_id', $this->location->id)->value('on_hand'),
                'reserved' => (int) DB::table('stock_levels')->where('variant_id', $this->s->id)->where('location_id', $this->location->id)->value('reserved'),
                'payment' => DB::table('payments')->where('order_id', $order->id)->first(['status', 'amount', 'refunded_amount']),
                'refund' => DB::table('refunds')->value('status'),
                'return' => $return->fresh()->status->value,
                'booked_at_carrier' => count(FakeApiCarrier::$booked),
            ],
            'sources' => [
                'confirmed' => $source('order_events', 'order_id', $order->id, 'confirmed'),
                'booked' => DB::table('shipment_events')->where('shipment_id', $shipment->id)->where('status', 'created')->value('source'),
                'approved' => $source('return_events', 'return_request_id', $return->id, 'approved'),
            ],
        ];
    };
});

dataset('checkpoints', [
    'mặc định — nhân viên bấm ở cả ba chốt' => [false, ['confirmed' => 'staff', 'booked' => 'system', 'approved' => 'staff']],
    'bật tự động cả ba chốt (điểm mở rộng)' => [true, ['confirmed' => 'system', 'booked' => 'system', 'approved' => 'system']],
]);

it('ba chốt có người: cùng tồn, cùng tiền dù nhân viên bấm hay bật tự động; lịch sử ghi đúng nguồn', function (bool $automated, array $sources) {
    $result = ($this->run)($automated);

    // `booked`: hãng API luôn được gọi trong job hệ thống — khác nhau là ai kích hoạt (nút "Đẩy lên hãng" hay on_create).
    expect($result['sources'])->toBe($sources)
        ->and($result['outcome'])->toEqual([
            'on_hand' => 9,
            'reserved' => 0,
            'payment' => (object) ['status' => 'partially_refunded', 'amount' => 600_000, 'refunded_amount' => 300_000],
            'refund' => 'completed',
            'return' => 'resolved',
            'booked_at_carrier' => 1,
        ]);
})->with('checkpoints');
