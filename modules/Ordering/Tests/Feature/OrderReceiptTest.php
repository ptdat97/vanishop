<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->api = '/api/storefront/v1';
    $created = $this->postJson("{$this->api}/carts")->assertCreated();
    $cartHeaders = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');
    $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $cartHeaders)->assertOk();
    $placed = $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 600_000]), [...$cartHeaders, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();
    $this->order = Order::query()->withoutGlobalScopes()->latest('id')->first();
    $this->headers = ['X-Vani-Order-Token' => $placed->json('data.access_token')];
    $this->ship = function (ShipmentStatus ...$statuses): void {
        app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($statuses) {
            $shipment = Shipment::query()->latest('id')->first();
            $service = app(FulfillmentService::class);
            if ($shipment->tracking_number === null) {
                $service->book($shipment->id, 'T'.$shipment->id);
            }
            foreach ($statuses as $status) {
                $service->updateStatus($shipment->id, $status, "test:{$status->value}", 'staff');
            }
        });
    };
    $this->confirm = fn (array $body = []) => $this->postJson("{$this->api}/orders/{$this->order->public_id}/received", $body, $this->headers);
});

it('chưa giao: không xác nhận được; đang giao quá số ngày cấu hình thì được', function () {
    ($this->confirm)()->assertStatus(409)->assertJsonPath('error.code', 'order.cannot_confirm_receipt');

    ($this->ship)(ShipmentStatus::PickedUp);
    $this->getJson("{$this->api}/orders/{$this->order->public_id}", $this->headers)->assertJsonPath('data.receipt.can_confirm', false);

    DB::table('order_events')->where('order_id', $this->order->id)->where('to_status', 'shipped')->update(['created_at' => now()->subDays(6)]);
    $this->getJson("{$this->api}/orders/{$this->order->public_id}", $this->headers)->assertJsonPath('data.receipt.can_confirm', true);
    ($this->confirm)()->assertOk()->assertJsonPath('data.receipt.returns_waived', false);
});

it('đã giao: xác nhận + không đổi trả → chặn đổi trả, ghi sự kiện nguồn customer; gọi lại không đổi gì; mặc định không hoàn tất sớm', function () {
    ($this->ship)(ShipmentStatus::PickedUp, ShipmentStatus::Delivered);

    ($this->confirm)(['waive_returns' => true])->assertOk()
        ->assertJsonPath('data.receipt.returns_waived', true)
        ->assertJsonPath('data.receipt.can_confirm', false)
        ->assertJsonPath('data.returnable.lines', []);
    ($this->confirm)()->assertOk()->assertJsonPath('data.receipt.returns_waived', true);

    expect(DB::table('order_events')->where('order_id', $this->order->id)->where('type', 'received_confirmed')->count())->toBe(1)
        ->and(DB::table('order_events')->where('order_id', $this->order->id)->where('type', 'received_confirmed')->value('source'))->toBe('customer')
        ->and($this->order->fresh()->order_status->value)->toBe('processing');

    $this->postJson("{$this->api}/orders/{$this->order->public_id}/returns", ['lines' => [['order_line_id' => $this->order->lines()->value('id'), 'quantity' => 1]], 'reason_code' => 'wrong_size'], $this->headers)
        ->assertStatus(422)->assertJsonPath('error.details.reason', 'waived');
});

it('bật hoàn tất khi khách xác nhận: không đổi trả → hoàn tất ngay; còn hạn đổi trả và không bỏ quyền → chỉ ghi nhận', function () {
    T::seed(fn () => app(Settings::class)->set('core', 'ordering.complete_on_customer_confirm', true));
    ($this->ship)(ShipmentStatus::PickedUp, ShipmentStatus::Delivered);

    ($this->confirm)()->assertOk();
    expect($this->order->fresh()->order_status->value)->toBe('processing');

    $this->order->refresh()->update(['meta' => array_diff_key($this->order->meta ?? [], array_flip(['received_confirmed_at', 'returns_waived']))]);
    ($this->confirm)(['waive_returns' => true])->assertOk();
    expect($this->order->fresh()->order_status->value)->toBe('completed');
});

it('trang đơn native: form "Đã nhận hàng" khi đã giao, gửi không cần JS', function () {
    ($this->ship)(ShipmentStatus::PickedUp, ShipmentStatus::Delivered);
    $this->withSession(["vani.orders.{$this->order->public_id}" => ['token' => $this->headers['X-Vani-Order-Token']]]);

    $this->get("/don-hang/{$this->order->public_id}")->assertOk()->assertSee('data-order-receipt', false);
    $this->post("/don-hang/{$this->order->public_id}/da-nhan", ['waive_returns' => '1'])->assertRedirect();
    $this->get("/don-hang/{$this->order->public_id}")->assertSee('data-order-received', false)->assertDontSee('data-order-receipt', false);
});
