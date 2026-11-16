<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderTransitionRejected;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Payments;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Tests\Feature\Fixtures\FakeCollectOnDeliveryGateway;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Plugin\HelloWorld\HelloWorldServiceProvider;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    FakeOnlineGateway::$refunds = [];
    FakeOnlineGateway::$queryResult = null;
    config(['vani.bank-transfer.account' => ['bank' => 'Vietcombank', 'account_number' => '0123456789', 'account_name' => 'CONG TY VANI']]);

    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->api = '/api/storefront/v1';
    $this->headers = [];
    $this->order = function (string $method, array $vouchers = [], int $expected = 330_000, string $key = 'order-key-0001') {
        $created = $this->postJson("{$this->api}/carts", [], $this->headers)->assertCreated();
        $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->putJson("{$this->api}/carts/{$cart}/vouchers", ['voucher_codes' => $vouchers], $headers)->assertOk();

        return $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => $expected]), [...$headers, 'Idempotency-Key' => $key]);
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
    expect(array_column($methods, 'code'))->toBe(['manual_bank_transfer', 'cod', 'fake_online']);

    config(['vani.cod.max_amount' => 100_000, 'vani.bank-transfer.account.account_number' => '']);
    $methods = $this->postJson("{$this->api}/checkout/{$created->json('data.id')}/quote", [], $headers)->json('data.payment_methods');
    expect(array_column($methods, 'code'))->toBe(['fake_online']);
});

it('COD mặc định: đơn chờ nhân viên xác nhận (ADR-046), payment chờ thu, không hết hạn, chưa có vận đơn', function () {
    ($this->order)('cod')->assertCreated()
        ->assertJsonPath('data.payment.method', 'cod')
        ->assertJsonPath('data.payment.status', 'pending')
        ->assertJsonPath('data.payment.action.type', 'none')
        ->assertJsonPath('data.payment.expires_at', null);

    expect(($this->orderRow)()->order_status->value)->toBe('pending')
        ->and(($this->orderRow)()->payment_status)->toBe('cod_pending')
        ->and(DB::table('order_events')->where('type', 'status_changed')->exists())->toBeFalse()
        ->and(DB::table('shipments')->exists())->toBeFalse();
});

it('COD bật tự xác nhận (điểm mở rộng): đơn xác nhận ngay với nguồn system, tạo vận đơn', function () {
    C::autoConfirmCod();
    ($this->order)('cod')->assertCreated();

    expect(($this->orderRow)()->order_status->value)->toBe('processing')
        ->and(DB::table('order_events')->where('type', 'status_changed')->where('to_status', 'confirmed')->first(['reason', 'source']))->toEqual((object) ['reason' => 'cod_auto_confirm', 'source' => 'system'])
        ->and(DB::table('shipments')->count())->toBe(1);
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
    C::promotion([], ['GIAM10' => 10]);
    $paymentId = ($this->order)('fake_online', ['GIAM10'], 300_000)->assertCreated()->json('data.payment.id');
    expect(($this->reserved)())->toBe(1)->and(DB::table('vouchers')->value('used_count'))->toEqual(1);

    $this->travel(16)->minutes();
    $this->artisan('vani:payment:expire')->assertSuccessful();

    $order = ($this->orderRow)();
    expect($order->order_status->value)->toBe('cancelled')
        ->and(DB::table('order_events')->where('to_status', 'cancelled')->value('reason'))->toBe('payment_timeout')
        ->and(($this->reserved)())->toBe(0)
        ->and(DB::table('vouchers')->value('used_count'))->toEqual(0)
        ->and(Payment::query()->withoutGlobalScopes()->sole()->status->value)->toBe('expired')
        ->and($order->payment_status)->toBe('voided');

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
    $base = '/admin/payment';
    $this->actingAs(T::staff(['admin.access', 'payments.view']), 'staff');
    $this->post("{$base}/payments/{$payment->id}/confirm", ['note' => 'VCB 123'])->assertForbidden();

    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.confirm', 'payments.refund']), 'staff');
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
    C::autoConfirmCod();
    ($this->order)('cod');
    $order = ($this->orderRow)();
    $transitions = app(OrderTransitions::class);

    expect($transitions->transition($order->id, OrderStatus::Confirmed, 'again', 'system'))->toBeFalse()
        ->and(fn () => $transitions->transition($order->id, OrderStatus::Pending, 'x', 'system'))
        ->toThrow(OrderTransitionRejected::class);
});

it('thu tiền khi giao là capability của cổng, không phải mã "cod": cổng plugin khai báo collectsOnDelivery được xử lý như COD', function () {
    app(Extensions::class)->tag([FakeCollectOnDeliveryGateway::class], GatewayRegistry::TAG);
    C::autoConfirmCod();

    ($this->order)('fake_pay_at_door')->assertCreated()->assertJsonPath('data.payment.method', 'fake_pay_at_door');

    $order = ($this->orderRow)();
    expect($order->payment_status)->toBe('cod_pending')
        ->and($order->order_status->value)->toBe('processing')
        ->and((int) DB::table('shipments')->where('order_id', $order->id)->value('cod_amount'))->toBe(330_000);
});

it('COD: nhân viên ghi nhận đã thu tiền (shop tự giao) — cần quyền xác nhận và ghi chú; hãng báo giao sau đó không ghi nhận lần hai', function () {
    ($this->order)('cod')->assertCreated();
    $payment = Payment::query()->withoutGlobalScopes()->sole();
    $base = '/admin/payment';
    $this->actingAs(T::staff(['admin.access', 'payments.view']), 'staff');
    $this->post("{$base}/payments/{$payment->id}/collect", ['note' => 'Shipper shop nộp'])->assertForbidden();
    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.confirm']), 'staff');
    $this->post("{$base}/payments/{$payment->id}/collect", [])->assertSessionHasErrors('note');

    $this->post("{$base}/payments/{$payment->id}/collect", ['note' => 'Shipper shop nộp'])->assertSessionHasNoErrors();
    app(PaymentService::class)->collectCod($payment->order_id, 330_000, 'shipment:1');

    expect($payment->fresh()->status->value)->toBe('paid')
        ->and(($this->orderRow)()->payment_status)->toBe('cod_collected')
        ->and(DB::table('payment_transactions')->where('type', 'cod_collected')->pluck('gateway_transaction_id')->all())->toBe(["manual:{$payment->public_id}"]);
    $this->get("{$base}/payments")->assertInertia(fn ($page) => $page->where('payments.0.can_collect', false)->where('payments.0.can_refund', true));
});

it('COD: đơn đã huỷ thì không ghi nhận đã thu được; danh sách tìm theo số đơn', function () {
    ($this->order)('cod')->assertCreated();
    $order = ($this->orderRow)();
    $payment = Payment::query()->withoutGlobalScopes()->sole();
    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.confirm']), 'staff');
    $this->get("/admin/payment/payments?q={$order->number}")->assertInertia(fn ($page) => $page->has('payments', 1)->where('payments.0.can_collect', true)->where('payments.0.order_url', route('admin.orders.orders.show', ['order' => $order->id])));
    $this->get('/admin/payment/payments?q=VN0000-999999')->assertInertia(fn ($page) => $page->has('payments', 0));
    app(OrderCommands::class)->cancel($order->id, 'Khách đổi ý', 'staff');

    $this->post("/admin/payment/payments/{$payment->id}/collect", ['note' => 'x'])->assertSessionHasErrors('business');

    expect($payment->fresh()->status->value)->toBe('cancelled')
        ->and(($this->orderRow)()->payment_status)->toBe('voided')
        ->and(DB::table('payment_transactions')->where('type', 'cod_collected')->count())->toBe(0);
});

it('hook vani.payment.before_refund (EP v3 X2): chặn hoàn tiền do nhân viên tạo, kèm lý do', function () {
    C::autoConfirmCod();
    ($this->order)('cod')->assertCreated();
    $payment = Payment::query()->withoutGlobalScopes()->sole();
    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.confirm', 'payments.refund']), 'staff');
    $this->post("/admin/payment/payments/{$payment->id}/collect", ['note' => 'thu tại quầy'])->assertSessionHasNoErrors();
    Hook::onValidate('vani.payment.before_refund', fn ($view, $amount): array => $amount->amount > 100_000 ? ['Trên 100.000đ cần quản lý duyệt'] : []);

    $this->post("/admin/payment/payments/{$payment->id}/refunds", ['amount' => 200_000, 'reason' => 'x', 'idempotency_key' => 'r1'])->assertSessionHasErrors(['business' => 'Chưa hoàn tiền được: Trên 100.000đ cần quản lý duyệt']);
    $this->post("/admin/payment/payments/{$payment->id}/refunds", ['amount' => 50_000, 'reason' => 'x', 'idempotency_key' => 'r2'])->assertSessionHasNoErrors();

    expect(DB::table('refunds')->pluck('amount')->all())->toBe([50_000]);
});

it('RefundPayout (EP v3 X5): "Chuyển trả qua …" ghi nhận đã chuyển trả với tham chiếu; kênh từ chối giữ "chờ chuyển trả"', function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install('vani.hello-world');
        app(PluginManager::class)->enable('vani.hello-world');
    });
    app()->register(HelloWorldServiceProvider::class);
    C::autoConfirmCod();
    ($this->order)('cod')->assertCreated();
    $payment = Payment::query()->withoutGlobalScopes()->sole();
    app(Payments::class)->collectCod($payment->order_id, 330_000, 'bang-ke:1');
    app(Payments::class)->collectCod($payment->order_id, 330_000, 'bang-ke:1');
    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.refund']), 'staff');
    $this->post("/admin/payment/payments/{$payment->id}/refunds", ['amount' => 50_000, 'reason' => 'x', 'idempotency_key' => 'r1'])->assertSessionHasNoErrors();
    $refund = DB::table('refunds')->sole();

    $this->get('/admin/payment/payments')->assertInertia(fn ($page) => $page->where('payouts', [['code' => 'hello', 'label' => 'Hello (thử)']]));
    $this->post("/admin/payment/refunds/{$refund->id}/payout/hello")->assertSessionHas('success', fn (string $message) => str_starts_with($message, 'Đã chuyển trả qua Hello (thử) (mã HELLO-'));

    expect(DB::table('refunds')->value('status'))->toBe('completed')
        ->and(app(Payments::class)->paymentId($payment->public_id))->toBe($payment->id)
        ->and(DB::table('payment_transactions')->where('type', 'cod_collected')->count())->toBe(1);
    $this->post("/admin/payment/refunds/{$refund->id}/payout/hello")->assertSessionHasErrors('business');
});

it('RefundPayout: khoản hoàn đang được chuyển trả không gọi kênh lần hai; nhân viên kiểm tra ngân hàng rồi ghi nhận đã chuyển trả', function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install('vani.hello-world');
        app(PluginManager::class)->enable('vani.hello-world');
    });
    app()->register(HelloWorldServiceProvider::class);
    C::autoConfirmCod();
    ($this->order)('cod')->assertCreated();
    $payment = Payment::query()->withoutGlobalScopes()->sole();
    app(Payments::class)->collectCod($payment->order_id, 330_000, 'bang-ke:1');
    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.refund']), 'staff');
    $this->post("/admin/payment/payments/{$payment->id}/refunds", ['amount' => 50_000, 'reason' => 'x', 'idempotency_key' => 'r1'])->assertSessionHasNoErrors();
    $refund = DB::table('refunds')->sole();
    // Lần bấm thứ nhất đã giành khoản hoàn và đang chờ ngân hàng (hoặc tiến trình chết sau khi gọi kênh).
    DB::table('refunds')->where('id', $refund->id)->update(['status' => 'paying_out']);

    $this->post("/admin/payment/refunds/{$refund->id}/payout/hello")->assertSessionHasErrors('business');
    $this->get('/admin/payment/payments')->assertInertia(fn ($page) => $page->where('refunds.0.paying_out', true));

    $this->post("/admin/payment/refunds/{$refund->id}/complete", ['note' => 'Đã kiểm tra sao kê'])->assertSessionHasNoErrors();
    expect(DB::table('refunds')->value('status'))->toBe('completed');
});

it('hãng báo thu COD khác số tiền của đơn: không ghi nhận đã thu, dòng lệch vào hàng đợi "Đối soát lệch" một lần', function () {
    C::autoConfirmCod();
    ($this->order)('cod')->assertCreated();
    $payment = Payment::query()->withoutGlobalScopes()->sole();

    app(Payments::class)->collectCod($payment->order_id, 300_000, 'shipment:1');
    app(Payments::class)->collectCod($payment->order_id, 300_000, 'shipment:1');

    expect($payment->refresh()->status->value)->toBe('pending')
        ->and(DB::table('reconciliation_entries')->get(['source', 'status', 'order_id', 'expected', 'actual'])->map(fn ($row) => (array) $row)->all())
        ->toBe([['source' => 'payment', 'status' => 'mismatch', 'order_id' => $payment->order_id, 'expected' => 330_000, 'actual' => 300_000]]);
});
