<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    ['s' => $this->s] = C::store();
    $this->api = '/api/storefront/v1';
    $this->place = function (string $method = 'fake_online') {
        $created = $this->postJson("{$this->api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $placed = $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();

        return [Order::query()->withoutGlobalScopes()->latest('id')->first(), ['X-Vani-Order-Token' => $placed->json('data.access_token')], $placed->json('data.payment.id')];
    };
    $this->retry = fn (Order $order, array $headers, array $body = []) => $this->postJson("{$this->api}/orders/{$order->public_id}/payment/retry", $body, $headers);
    $this->fail = fn (string $paymentId) => $this->postJson('/api/payments/fake_online/callback', FakeOnlineGateway::callbackPayload($paymentId, 'TXN-FAIL', 'failed', 330_000));
});

it('khoản đang chờ: thanh toán lại dùng lại khoản đó (link của cổng); khoản lỗi: lượt mới cùng số tiền, cùng hạn', function () {
    [$order, $headers, $paymentId] = ($this->place)();

    ($this->retry)($order, $headers)->assertOk()
        ->assertJsonPath('data.id', $paymentId)
        ->assertJsonPath('data.retryable', true)
        ->assertJsonPath('data.action.url', "https://pay.example/checkout/{$paymentId}");

    ($this->fail)($paymentId)->assertOk();
    $old = Payment::query()->where('public_id', $paymentId)->first();
    expect($old->status->value)->toBe('failed');

    $retried = ($this->retry)($order, $headers)->assertOk()->json('data');
    $next = Payment::query()->where('public_id', $retried['id'])->first();
    expect($retried['id'])->not->toBe($paymentId)
        ->and($retried['action']['url'])->toBe("https://pay.example/checkout/{$retried['id']}")
        ->and($old->fresh()->status->value)->toBe('cancelled')
        ->and($next->amount)->toBe(330_000)
        ->and($next->expires_at->equalTo($old->expires_at))->toBeTrue()
        ->and($next->meta['retry_of'])->toBe($paymentId);
});

it('không thanh toán lại: đơn COD, đã trả, đã hết hạn, cổng không phải online', function () {
    [$cod, $codHeaders] = ($this->place)('cod');
    ($this->retry)($cod, $codHeaders)->assertStatus(422)->assertJsonPath('error.code', 'payment.gateway_unavailable');

    [$order, $headers, $paymentId] = ($this->place)();
    ($this->retry)($order, $headers, ['gateway' => 'cod'])->assertStatus(422)->assertJsonPath('error.code', 'payment.gateway_unavailable');

    Payment::query()->where('public_id', $paymentId)->update(['expires_at' => now()->subMinute()]);
    ($this->retry)($order, $headers)->assertStatus(409)->assertJsonPath('error.code', 'payment.expired');

    Payment::query()->where('public_id', $paymentId)->update(['expires_at' => now()->addMinutes(10), 'status' => 'paid']);
    ($this->retry)($order, $headers)->assertStatus(409)->assertJsonPath('error.code', 'payment.invalid_state');
});

it('trang đơn native: hiện nút thanh toán theo khoản mới nhất (không cần phiên lúc đặt), bấm → sang cổng', function () {
    [$order, $headers, $paymentId] = ($this->place)();
    ($this->fail)($paymentId)->assertOk();
    $this->withSession(["vani.orders.{$order->public_id}" => ['token' => $headers['X-Vani-Order-Token']]]);

    $this->get("/don-hang/{$order->public_id}")->assertOk()->assertSee('data-payment-retry', false)->assertSee('Thanh toán lại');
    $redirect = $this->post("/don-hang/{$order->public_id}/thanh-toan")->assertRedirect()->headers->get('Location');
    $latest = Payment::query()->where('order_id', $order->id)->latest('id')->first();
    expect($redirect)->toBe("https://pay.example/checkout/{$latest->public_id}")->and($latest->public_id)->not->toBe($paymentId);

    T::seed(fn () => $latest->update(['status' => 'paid']));
    $this->get("/don-hang/{$order->public_id}")->assertSee('Đã nhận thanh toán.')->assertDontSee('data-payment-retry', false);
});
