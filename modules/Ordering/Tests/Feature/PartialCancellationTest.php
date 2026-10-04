<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    ['s' => $this->s, 'm' => $this->m] = C::store();
    $this->place = function (string $method = 'cod', int $expected = 500_000) {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->m->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => $expected]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->admin = fn (Order $order) => "/admin/orders/orders/{$order->id}";
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', 'orders.cancel']), 'staff');
});

it('Admin: nút huỷ một phần, giảm dòng + tổng, ghi lịch sử; khoá lạc quan', function () {
    $order = ($this->place)();
    $sLine = $order->lines()->where('variant_id', $this->s->id)->first();
    $this->get(($this->admin)($order))->assertInertia(fn (Assert $page) => $page->where('can.cancel_lines', true));

    $this->post(($this->admin)($order).'/cancel-lines', ['lines' => [$sLine->id => 1], 'reason' => 'hết hàng', 'lock_version' => $order->lock_version + 5])->assertSessionHasErrors('business');
    $this->post(($this->admin)($order).'/cancel-lines', ['lines' => [$sLine->id => 1], 'reason' => 'hết hàng', 'lock_version' => $order->lock_version])->assertSessionHasNoErrors();

    $order->refresh();
    expect([$order->total_amount, $sLine->fresh()->quantity, $sLine->fresh()->cancelled_quantity])->toBe([200_000, 0, 1])
        ->and(Shipment::query()->where('status', '!=', 'cancelled')->sole()->lines()->pluck('quantity', 'variant_id')->all())->toEqual([$this->m->id => 1])
        ->and(DB::table('order_events')->where('order_id', $order->id)->where('type', 'lines_cancelled')->exists())->toBeTrue();
    $this->get(($this->admin)($order))->assertInertia(fn (Assert $page) => $page->where('order.lines', fn ($lines) => collect($lines)->firstWhere('id', $sLine->id)['cancelled_quantity'] === 1));
});

it('từ chối: huỷ hết (phải huỷ cả đơn), vượt số lượng, dòng lạ, online chưa trả, hàng đã rời kho, thiếu quyền', function () {
    $order = ($this->place)();
    [$sLine, $mLine] = $order->lines()->orderBy('id')->get()->all();
    $post = fn (array $lines, ?Order $target = null) => $this->post(($this->admin)($target ?? $order).'/cancel-lines', ['lines' => $lines, 'reason' => 'x', 'lock_version' => ($target ?? $order)->fresh()->lock_version]);

    $post([$sLine->id => 1, $mLine->id => 1])->assertSessionHasErrors('business');
    $post([$sLine->id => 2])->assertSessionHasErrors('business');
    $post([$sLine->id + 999 => 1])->assertSessionHasErrors('business');

    $online = ($this->place)('fake_online');
    expect($online->payment_status)->toBe('unpaid');
    $post([$online->lines()->value('id') => 1], $online)->assertSessionHasErrors('business');

    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        $shipment = Shipment::query()->where('status', '!=', 'cancelled')->orderBy('id')->first();
        app(FulfillmentService::class)->book($shipment->id, 'T1');
        app(FulfillmentService::class)->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'e1', 'staff');
    });
    $post([$sLine->id => 1])->assertSessionHasErrors('business');

    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    $post([$mLine->id => 1])->assertForbidden();
});
