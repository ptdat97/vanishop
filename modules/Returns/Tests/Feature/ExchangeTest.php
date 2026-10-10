<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Returns\Contracts\ReturnRejected;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Đổi hàng (roadmap Phase 2, order §7.1): sau khi nhận hàng trả mới tạo đơn thay thế (source = exchange, parent_order_id);
| cùng mẫu đổi size/màu không tính chênh; khác mẫu theo giá hiện tại, khách bù (COD) hoặc được hoàn phần thừa; phí giao 0.
*/

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m, 'location' => $this->location] = C::store(); // cùng mẫu "Đầm lụa": S 300k, M 200k
    [$this->premium] = P::variants(T::product(null, ['name' => 'Áo khoác']), ['L']);
    [$this->basic] = P::variants(T::product(null, ['name' => 'Áo thun']), ['L']);
    P::priceList(['code' => 'other'], [$this->premium->id => [450_000], $this->basic->id => [100_000]]);
    I::stock($this->location, $this->premium->id, 5);
    I::stock($this->location, $this->basic->id, 1);
    $this->api = '/api/storefront/v1';
    $this->admin = '/admin/returns/returns';
    $this->onHand = fn (int $variantId): int => (int) DB::table('stock_levels')->where('variant_id', $variantId)->where('location_id', $this->location->id)->value('on_hand');

    // Đơn COD 1 × S (300.000 ₫), đã giao.
    $this->deliveredOrder = function (): array {
        $created = $this->postJson("{$this->api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $placed = $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();
        deliverShipment(Shipment::query()->latest('id')->first());

        return [Order::query()->latest('id')->first(), ['X-Vani-Order-Token' => $placed->json('data.access_token')]];
    };
    $this->requestExchange = fn (Order $order, array $headers, ?int $variantId) => $this->postJson("{$this->api}/orders/{$order->public_id}/returns", [
        'lines' => [['order_line_id' => $order->lines()->value('id'), 'quantity' => 1, 'exchange_variant_id' => $variantId]], 'reason_code' => 'wrong_size',
    ], $headers);
    // Duyệt → nhận hàng (bán được) qua Admin.
    $this->receive = function (ReturnRequest $return): void {
        $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'approved', 'lock_version' => $return->fresh()->lock_version])->assertSessionHasNoErrors();
        $this->post("{$this->admin}/{$return->id}/receive", ['conditions' => []])->assertSessionHasNoErrors();
    };
    $this->actingAs(T::staff(['admin.access', 'returns.view', 'returns.manage', 'returns.refund', 'orders.view']), 'staff');
});

function deliverShipment(Shipment $shipment): void
{
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($shipment) {
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'T'.$shipment->id);
        foreach ([ShipmentStatus::PickedUp, ShipmentStatus::Delivered] as $status) {
            $service->updateStatus($shipment->id, $status, "test:{$shipment->id}:{$status->value}", 'staff');
        }
    });
}

it('cùng mẫu đổi size: chỉ tạo đơn thay thế SAU khi nhận hàng trả; 0đ, miễn phí giao, giữ hàng + vận đơn như đơn thường', function () {
    [$order, $headers] = ($this->deliveredOrder)();
    $stockM = ($this->onHand)($this->m->id);

    ($this->requestExchange)($order, $headers, $this->m->id)->assertCreated();
    $return = ReturnRequest::query()->sole();
    expect($return->resolution)->toBe('exchange')->and(Order::query()->count())->toBe(1);

    // Chưa nhận hàng → chưa hoàn tất được.
    $this->post("{$this->admin}/{$return->id}/resolve", [])->assertSessionHasErrors();
    ($this->receive)($return);
    expect(Order::query()->count())->toBe(1);

    $this->get("{$this->admin}/{$return->id}")->assertInertia(fn ($page) => $page->where('exchangeQuote.payable', 0)->where('exchangeQuote.refund', 0)->where('exchangeQuote.lines.0.same_style', true));
    $this->post("{$this->admin}/{$return->id}/resolve", [])->assertSessionHasNoErrors();

    $replacement = Order::query()->where('parent_order_id', $order->id)->sole();
    $line = $replacement->lines()->sole();
    expect($replacement->source)->toBe('exchange')
        ->and([$replacement->total_amount, $replacement->shipping_amount, $replacement->discount_amount])->toBe([0, 0, 300_000])
        ->and([$line->variant_id, $line->unit_amount, $line->total_amount, $line->tax_amount])->toBe([$this->m->id, 300_000, 0, 0])
        ->and($replacement->order_status->value)->toBe('processing') // xác nhận ngay, vận đơn tạo tự động
        ->and([$replacement->payment_method, $replacement->payment_status])->toBe(['exchange', 'paid'])
        ->and(DB::table('payments')->where('order_id', $replacement->id)->exists())->toBeFalse() // 0đ: không có khoản thu
        ->and(DB::table('order_adjustments')->where('order_id', $replacement->id)->where('type', 'exchange_credit')->value('amount'))->toBe(-300_000)
        ->and(DB::table('stock_reservations')->where('reservation_key', $replacement->reservation_key)->where('status', 'active')->sum('quantity'))->toEqual(1)
        ->and(Shipment::query()->where('order_id', $replacement->id)->where('status', '!=', 'cancelled')->value('cod_amount'))->toBe(0)
        ->and($return->fresh())->status->value->toBe('resolved')->refunded_amount->toBe(0)->replacement_order_id->toBe($replacement->id)
        ->and(($this->onHand)($this->m->id))->toBe($stockM); // giữ hàng, chưa trừ (trừ khi rời kho)

    $created = json_decode((string) DB::table('integration_events')->where('aggregate_id', $replacement->number)->where('event_type', 'order.created')->value('payload'), true);
    $resolved = json_decode((string) DB::table('integration_events')->where('aggregate_id', $order->number)->where('event_type', 'return.resolved')->value('payload'), true);
    expect($created['data']['order']['parent_order_number'] ?? $created['order']['parent_order_number'] ?? null)->toBe($order->number)
        ->and($resolved['data'] ?? $resolved)->toMatchArray(['resolution' => 'exchange', 'replacement_order_number' => $replacement->number]);
});

it('khác mẫu đắt hơn: khách bù phần chênh qua COD trên đơn thay thế; rẻ hơn: hoàn phần thừa trên đơn gốc', function () {

    [$order, $headers] = ($this->deliveredOrder)();
    ($this->requestExchange)($order, $headers, $this->premium->id)->assertCreated();
    $return = ReturnRequest::query()->latest('id')->first();
    ($this->receive)($return);
    $this->post("{$this->admin}/{$return->id}/resolve", [])->assertSessionHasNoErrors();
    $replacement = Order::query()->where('parent_order_id', $order->id)->sole();
    expect([$replacement->subtotal_amount, $replacement->discount_amount, $replacement->total_amount])->toBe([450_000, 300_000, 150_000])
        ->and((int) DB::table('payments')->where('order_id', $replacement->id)->value('amount'))->toBe(150_000)
        ->and($return->fresh()->refunded_amount)->toBe(0)
        ->and($replacement->total_amount)->toBe((int) $replacement->lines()->sum('total_amount'));

    [$second, $secondHeaders] = ($this->deliveredOrder)();
    ($this->requestExchange)($second, $secondHeaders, $this->basic->id)->assertCreated();
    $cheaper = ReturnRequest::query()->latest('id')->first();
    ($this->receive)($cheaper);
    $this->post("{$this->admin}/{$cheaper->id}/resolve", [])->assertSessionHasNoErrors();
    expect(Order::query()->where('parent_order_id', $second->id)->sole()->total_amount)->toBe(0)
        ->and($cheaper->fresh()->refunded_amount)->toBe(200_000)
        // COD đã thu → hoàn tay (yêu cầu hoàn) 200k trên đơn gốc.
        ->and((int) DB::table('refunds')->join('payments', 'payments.id', '=', 'refunds.payment_id')->where('payments.order_id', $second->id)->sum('refunds.amount'))->toBe(200_000);
});

it('hết hàng thay thế → không hoàn tất, yêu cầu giữ nguyên để nhân viên xử lý; variant lạ/thiếu dòng → từ chối khi tạo', function () {
    [$order, $headers] = ($this->deliveredOrder)();

    ($this->requestExchange)($order, $headers, 999_999)->assertStatus(422)->assertJsonPath('error.code', 'return.exchange_invalid');

    I::stock($this->location, $this->basic->id, 0);
    ($this->requestExchange)($order, $headers, $this->basic->id)->assertCreated();
    $return = ReturnRequest::query()->sole();
    ($this->receive)($return);

    $this->post("{$this->admin}/{$return->id}/resolve", [])->assertSessionHasErrors('business');
    expect($return->fresh()->status->value)->toBe('received')
        ->and(Order::query()->where('parent_order_id', $order->id)->exists())->toBeFalse()
        ->and(DB::table('payments')->count())->toBe(1);
});

it('khách phải bù chênh mà không còn cổng thu khi giao nhận giao dịch mới → không hoàn tất (Core không gắn mã cổng)', function () {
    expect(collect(app(PluginDoctor::class)->diagnose())->pluck('code'))->not->toContain('collect_on_delivery_missing');
    [$order, $headers] = ($this->deliveredOrder)();
    ($this->requestExchange)($order, $headers, $this->premium->id)->assertCreated();
    $return = ReturnRequest::query()->sole();
    ($this->receive)($return);

    DB::table('plugins')->where('id', 'vani.cod')->update(['status' => 'draining']);
    PluginActivation::forgetCache();
    app(PluginActivation::class)->flush();

    $this->post("{$this->admin}/{$return->id}/resolve", [])->assertSessionHasErrors('business');
    expect($return->fresh()->status->value)->toBe('received')
        ->and(Order::query()->where('parent_order_id', $order->id)->exists())->toBeFalse()
        // Doctor báo trước tình huống này (Payment đăng ký kiểm tra qua Extensions::doctorCheck).
        ->and(collect(app(PluginDoctor::class)->diagnose())->pluck('code'))->toContain('collect_on_delivery_missing');
});

it('nhân viên tạo yêu cầu đổi hàng hộ khách bằng SKU thay thế', function () {
    [$order] = ($this->deliveredOrder)();
    $lineId = $order->lines()->value('id');

    $this->post($this->admin, ['order_id' => $order->id, 'lines' => [$lineId => 1], 'reason_code' => 'wrong_size', 'exchange_skus' => [$lineId => 'KHONG-CO']])->assertSessionHasErrors("exchange_skus.{$lineId}");
    $this->post($this->admin, ['order_id' => $order->id, 'lines' => [$lineId => 1], 'reason_code' => 'wrong_size', 'exchange_skus' => [$lineId => strtolower($this->m->sku)]])->assertRedirect();

    expect(ReturnRequest::query()->sole())->resolution->toBe('exchange')
        ->and(DB::table('return_lines')->value('exchange_variant_id'))->toBe($this->m->id);
});

it('đơn thay thế chỉ đổi tiếp size/màu cùng mẫu; trả hoàn tiền / đổi mẫu khác → từ chối (tiền thật nằm ở đơn gốc)', function () {
    [$order, $headers] = ($this->deliveredOrder)();
    ($this->requestExchange)($order, $headers, $this->m->id)->assertCreated();
    $return = ReturnRequest::query()->sole();
    ($this->receive)($return);
    $this->post("{$this->admin}/{$return->id}/resolve", [])->assertSessionHasNoErrors();
    $replacement = Order::query()->where('parent_order_id', $order->id)->sole();
    deliverShipment(Shipment::query()->where('order_id', $replacement->id)->where('status', '!=', 'cancelled')->sole());

    $lineId = $replacement->lines()->value('id');
    $request = fn (?int $variantId) => app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(Returns::class)
        ->request($replacement->id, [$lineId => 1], 'wrong_size', null, 'staff', $variantId === null ? [] : [$lineId => $variantId]));

    expect(fn () => $request(null))->toThrow(ReturnRejected::class)
        ->and(fn () => $request($this->premium->id))->toThrow(ReturnRejected::class);
    expect($request($this->s->id)->resolution)->toBe('exchange');
});
