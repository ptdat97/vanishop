<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Events\PaymentAuthorized;
use Modules\Payment\Events\PaymentCaptured;
use Modules\Payment\Testing\PaymentGatewayContract;
use Modules\Payment\Tests\Feature\Fixtures\FakeCardGateway;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

PaymentGatewayContract::define(
    'fake_card (CapturesLater)',
    fn () => new FakeCardGateway,
    validCallback: fn ($payment) => Request::create('/callback', 'POST', FakeOnlineGateway::callbackPayload($payment->publicId, 'T1', 'authorized', $payment->amount->amount)),
    tamperedCallback: fn ($payment) => Request::create('/callback', 'POST', [...FakeOnlineGateway::callbackPayload($payment->publicId, 'T1', 'authorized', $payment->amount->amount), 'amount' => '1']),
);

beforeEach(function () {
    app(Extensions::class)->tag([FakeCardGateway::class], GatewayRegistry::TAG);
    FakeCardGateway::$captures = [];
    FakeCardGateway::$voids = [];
    FakeCardGateway::$failCapture = false;
    ['s' => $this->s] = C::store();
    $this->events = [];
    Event::listen([PaymentAuthorized::class, PaymentCaptured::class], function (object $event): void {
        $this->events[] = $event::class;
    });

    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $paymentId = $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['payment_method' => 'fake_card', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'card-order-1'])
        ->assertCreated()->json('data.payment.id');
    $this->authorize = fn () => $this->postJson('/api/payments/fake_card/callback', FakeOnlineGateway::callbackPayload($paymentId, 'AUTH-1', 'authorized', 330_000))->assertOk();
    $this->order = fn () => Order::query()->withoutGlobalScopes()->sole();
    $this->payment = fn () => DB::table('payments')->sole();
    $this->shipOut = function (): void {
        app(CurrentContext::class)->runAs(ContextScope::system('test'), function (): void {
            $shipment = Shipment::query()->sole();
            app(FulfillmentService::class)->book($shipment->id, 'TRK-1');
            app(FulfillmentService::class)->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'pick-1', 'staff');
        });
    };
});

it('giữ tiền → xác nhận đơn; vận đơn rời kho → thu tiền (một lần), đơn chuyển "đã thanh toán"', function () {
    ($this->authorize)();
    expect(($this->payment)()->status)->toBe('authorized')
        ->and(($this->order)()->payment_status)->toBe('authorized')
        ->and(($this->order)()->order_status->value)->toBe('processing')
        ->and($this->events)->toBe([PaymentAuthorized::class]);

    ($this->authorize)(); // IPN trùng
    ($this->shipOut)();

    expect(($this->payment)()->status)->toBe('paid')
        ->and(($this->order)()->payment_status)->toBe('paid')
        ->and(FakeCardGateway::$captures)->toHaveCount(1)
        ->and($this->events)->toBe([PaymentAuthorized::class, PaymentCaptured::class])
        ->and(DB::table('payment_transactions')->where('type', 'capture')->count())->toBe(1);
});

it('cổng từ chối thu khi rời kho → vẫn giữ tiền; nhân viên thu lại từ Admin; capture_on = manual thì không tự thu', function () {
    config(['vanishop.payment.capture_on' => 'manual']);
    ($this->authorize)();
    ($this->shipOut)();
    expect(($this->payment)()->status)->toBe('authorized');

    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.confirm']), 'staff');
    $payment = ($this->payment)();
    FakeCardGateway::$failCapture = true;
    $this->from('/admin/payment/payments')->post("/admin/payment/payments/{$payment->id}/capture")->assertSessionHasErrors('business');
    expect(($this->payment)()->status)->toBe('authorized');

    FakeCardGateway::$failCapture = false;
    $this->post("/admin/payment/payments/{$payment->id}/capture")->assertSessionHasNoErrors();
    expect(($this->payment)()->status)->toBe('paid');
});

it('đơn huỷ khi đang giữ tiền → huỷ giữ tiền ở cổng, payment cancelled, không tạo hoàn tiền', function () {
    ($this->authorize)();
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.cancel']), 'staff');
    $this->post('/admin/orders/orders/'.($this->order)()->id.'/cancel', ['reason' => 'khách đổi ý'])->assertSessionHasNoErrors();

    expect(($this->payment)()->status)->toBe('cancelled')
        ->and(FakeCardGateway::$voids)->toHaveCount(1)
        ->and(DB::table('refunds')->count())->toBe(0);
});
