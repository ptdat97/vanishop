<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderTransitionRejected;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Domain\Money\Money;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    FakeOnlineGateway::$refunds = [];
    FakeOnlineGateway::$queryResult = null;
    config(['vanishop.payment.bank_transfer.accounts.default' => ['bank' => 'Vietcombank', 'account_number' => '0123456789', 'account_name' => 'CONG TY VANI']]);

    ['brand' => $this->brand, 'channel' => $this->channel, 's' => $this->s] = C::store();
    $this->api = '/api/storefront/v1';
    $this->headers = ['X-Vani-Channel' => 'web-lumiere'];
    $this->order = function (string $method, array $vouchers = [], int $expected = 330_000, string $key = 'order-key-0001') {
        $created = $this->postJson("{$this->api}/carts", [], $this->headers)->assertCreated();
        $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'voucher_codes' => $vouchers, 'expected_total' => $expected]), [...$headers, 'Idempotency-Key' => $key]);
    };
    $this->callback = fn (string $paymentId, string $txn, string $status = 'paid', int $amount = 330_000) => $this->postJson('/api/payments/fake_online/callback', FakeOnlineGateway::callbackPayload($paymentId, $txn, $status, $amount));
    $this->orderRow = fn () => Order::query()->withoutGlobalScopes()->sole();
    $this->reserved = fn () => (int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('reserved');
});

it('quote liệt kê các cổng khả dụng', function () {
    $created = $this->postJson("{$this->api}/carts", [], $this->headers);
    $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("{$this->api}/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers);

    $methods = $this->postJson("{$this->api}/checkout/{$created->json('data.id')}/quote", [], $headers)->json('data.payment_methods');
    expect(array_column($methods, 'code'))->toBe(['cod', 'manual_bank_transfer', 'fake_online']);

    config(['vanishop.payment.cod.max_amount' => 100_000, 'vanishop.payment.bank_transfer.accounts.default.account_number' => '']);
    $methods = $this->postJson("{$this->api}/checkout/{$created->json('data.id')}/quote", [], $headers)->json('data.payment_methods');
    expect(array_column($methods, 'code'))->toBe(['fake_online']);
});

it('COD: đơn tự xác nhận, payment chờ thu, không hết hạn', function () {
    ($this->order)('cod')->assertCreated()
        ->assertJsonPath('data.payment.method', 'cod')
        ->assertJsonPath('data.payment.status', 'pending')
        ->assertJsonPath('data.payment.action.type', 'none')
        ->assertJsonPath('data.payment.expires_at', null);

    expect(($this->orderRow)()->order_status->value)->toBe('processing')
        ->and(($this->orderRow)()->payment_status)->toBe('cod_pending')
        ->and(DB::table('order_events')->where('type', 'status_changed')->value('reason'))->toBe('cod_auto_confirm');
});

it('COD: tắt tự xác nhận thì đơn chờ CSKH', function () {
    config(['vanishop.payment.cod.auto_confirm' => false]);
    ($this->order)('cod')->assertCreated();

    expect(($this->orderRow)()->order_status->value)->toBe('pending');
});

it('online: redirect → IPN hợp lệ → đơn đã thanh toán + xác nhận; IPN trùng không xử lý lại', function () {
    $response = ($this->order)('fake_online')->assertCreated()
        ->assertJsonPath('data.payment.action.type', 'redirect')
        ->assertJsonPath('data.payment.status', 'pending');
    $paymentId = $response->json('data.payment.id');
    expect($response->json('data.payment.action.url'))->toBe("https://pay.example/checkout/{$paymentId}")
        ->and($response->json('data.payment.expires_at'))->not->toBeNull()
        ->and(($this->orderRow)()->order_status->value)->toBe('pending')
        ->and(($this->orderRow)()->payment_status)->toBe('unpaid');

    ($this->callback)($paymentId, 'TXN-1')->assertOk()->assertExactJson(['RspCode' => '00']);
    ($this->callback)($paymentId, 'TXN-1')->assertOk();

    $order = ($this->orderRow)();
    expect($order->order_status->value)->toBe('processing')
        ->and($order->payment_status)->toBe('paid')
        ->and(DB::table('payment_transactions')->where('type', 'callback')->count())->toBe(1)
        ->and(DB::table('order_events')->where('order_id', $order->id)->where('type', 'status_changed')->where('to_status', 'confirmed')->value('source'))->toBe('gateway:fake_online');

    $this->getJson("{$this->api}/payments/{$paymentId}", $this->headers)->assertOk()->assertJsonPath('data.status', 'paid')->assertJsonPath('data.order.number', $order->number)->assertJsonPath('data.action', null);
});

it('online: chữ ký sai → 400; sai số tiền → không ghi nhận', function () {
    $paymentId = ($this->order)('fake_online')->json('data.payment.id');

    $this->postJson('/api/payments/fake_online/callback', [...FakeOnlineGateway::callbackPayload($paymentId, 'TXN-1', 'paid', 330_000), 'amount' => '1'])
        ->assertStatus(400)->assertJsonPath('error.code', 'payment.invalid_callback');
    ($this->callback)($paymentId, 'TXN-2', 'paid', 1_000)->assertOk();
    $this->postJson('/api/payments/cod/callback', [])->assertNotFound();
    $this->postJson('/api/payments/khong-co/callback', [])->assertNotFound();

    expect(Payment::query()->withoutGlobalScopes()->sole()->status->value)->toBe('pending')
        ->and(($this->orderRow)()->payment_status)->toBe('unpaid');
});

it('online: hết hạn thanh toán → huỷ đơn, nhả hàng, hoàn lượt voucher; IPN muộn → tự hoàn tiền', function () {
    C::promotion($this->brand, [], ['GIAM10' => 10]);
    $paymentId = ($this->order)('fake_online', ['GIAM10'], 300_000)->assertCreated()->json('data.payment.id');
    expect(($this->reserved)())->toBe(1)->and(DB::table('vouchers')->value('used_count'))->toEqual(1);

    $this->travel(16)->minutes();
    $this->artisan('vani:payment:expire')->assertSuccessful();

    $order = ($this->orderRow)();
    expect($order->order_status->value)->toBe('cancelled')
        ->and(DB::table('order_events')->where('to_status', 'cancelled')->value('reason'))->toBe('payment_timeout')
        ->and(($this->reserved)())->toBe(0)
        ->and(DB::table('vouchers')->value('used_count'))->toEqual(0)
        ->and(Payment::query()->withoutGlobalScopes()->sole()->status->value)->toBe('expired');

    ($this->callback)($paymentId, 'TXN-LATE', 'paid', 300_000)->assertOk();

    $payment = Payment::query()->withoutGlobalScopes()->sole();
    expect($payment->status->value)->toBe('refunded')
        ->and(DB::table('refunds')->value('status'))->toBe('completed')
        ->and(DB::table('refunds')->value('reason'))->toBe('late_payment_after_cancel')
        ->and(($this->orderRow)()->payment_status)->toBe('refunded')
        ->and(($this->orderRow)()->order_status->value)->toBe('cancelled');
});

it('online: không nhận IPN → job truy vấn cổng ghi nhận thanh toán', function () {
    $paymentId = ($this->order)('fake_online')->json('data.payment.id');
    FakeOnlineGateway::$queryResult = new GatewayStatus(GatewayCallback::PAID, 'TXN-Q', Money::vnd(330_000));

    $this->artisan('vani:payment:reconcile')->assertSuccessful();
    expect(Payment::query()->withoutGlobalScopes()->sole()->status->value)->toBe('pending');

    $this->travel(6)->minutes();
    $this->artisan('vani:payment:reconcile')->assertSuccessful();
    expect(Payment::query()->withoutGlobalScopes()->where('public_id', $paymentId)->sole()->status->value)->toBe('paid')
        ->and(($this->orderRow)()->order_status->value)->toBe('processing');
});

it('chuyển khoản thủ công: hướng dẫn chuyển khoản, nhân viên xác nhận, hoàn tiền một phần chờ chuyển trả', function () {
    ($this->order)('manual_bank_transfer')->assertCreated()
        ->assertJsonPath('data.payment.action.type', 'instructions')
        ->assertJsonPath('data.payment.action.instructions.account_number', '0123456789')
        ->assertJsonPath('data.payment.action.instructions.transfer_content', ($this->orderRow)()->number);

    $payment = Payment::query()->withoutGlobalScopes()->sole();
    $base = '/admin/payment/lumiere';
    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'payments.view']), 'staff');
    $this->post("{$base}/payments/{$payment->id}/confirm", ['note' => 'VCB 123'])->assertForbidden();

    $this->actingAs(T::staffFor($this->brand, ['admin.access', 'payments.view', 'payments.confirm', 'payments.refund']), 'staff');
    $this->get("{$base}/payments")->assertOk();
    $this->post("{$base}/payments/{$payment->id}/confirm", ['note' => 'VCB 123'])->assertSessionHasNoErrors();
    $this->post("{$base}/payments/{$payment->id}/confirm", ['note' => 'lần 2'])->assertSessionHasNoErrors();

    expect($payment->fresh()->status->value)->toBe('paid')
        ->and(($this->orderRow)()->order_status->value)->toBe('processing')
        ->and(DB::table('payment_transactions')->where('type', 'confirm')->count())->toBe(1);

    $this->post("{$base}/payments/{$payment->id}/refunds", ['amount' => 500_000, 'reason' => 'x', 'idempotency_key' => 'k1'])->assertSessionHasErrors('business');
    $this->post("{$base}/payments/{$payment->id}/refunds", ['amount' => 100_000, 'reason' => 'Đổi size', 'idempotency_key' => 'k2'])->assertSessionHasNoErrors();

    expect($payment->fresh()->status->value)->toBe('partially_refunded')
        ->and($payment->fresh()->refunded_amount)->toBe(100_000)
        ->and(DB::table('refunds')->value('status'))->toBe('requested')
        ->and(($this->orderRow)()->payment_status)->toBe('partially_refunded');

    $refundId = DB::table('refunds')->value('id');
    $this->post("{$base}/refunds/{$refundId}/complete", ['note' => 'Đã CK trả'])->assertSessionHasNoErrors();
    expect(DB::table('refunds')->value('status'))->toBe('completed');
});

it('state machine: không chuyển ngược trạng thái; chuyển trùng là no-op', function () {
    config(['vanishop.fulfillment.auto_create' => false]);
    ($this->order)('cod');
    $order = ($this->orderRow)();
    $transitions = app(OrderTransitions::class);

    expect($transitions->transition($order->id, OrderStatus::Confirmed, 'again', 'system'))->toBeFalse()
        ->and(fn () => $transitions->transition($order->id, OrderStatus::Pending, 'x', 'system'))
        ->toThrow(OrderTransitionRejected::class);
});
