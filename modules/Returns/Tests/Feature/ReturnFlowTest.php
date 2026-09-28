<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Events\ReturnResolved;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['brand' => $this->brand, 'channel' => $this->channel, 's' => $this->s, 'location' => $this->location] = C::store();
    $this->api = '/api/storefront/v1';
    $this->headers = ['X-Vani-Channel' => 'web-lumiere'];
    $this->admin = '/admin/returns/lumiere/returns';

    // Đơn COD 2 × S (600.000 ₫, miễn phí giao), đã giao.
    $this->placeAndDeliver = function (array $vouchers = [], int $expected = 600_000, bool $deliver = true): array {
        $created = $this->postJson("{$this->api}/carts", [], $this->headers)->assertCreated();
        $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)->assertOk();
        $placed = $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => $expected, 'voucher_codes' => $vouchers]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();

        if ($deliver) {
            app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
                $shipment = Shipment::query()->latest('id')->first();
                $service = app(FulfillmentService::class);
                $service->book($shipment->id, 'T'.$shipment->id);
                foreach ([ShipmentStatus::PickedUp, ShipmentStatus::Delivered] as $status) {
                    $service->updateStatus($shipment->id, $status, "test:{$status->value}", 'staff');
                }
            });
        }

        $order = Order::query()->withoutGlobalScopes()->latest('id')->first();

        return [$order, [...$this->headers, 'X-Vani-Order-Token' => $placed->json('data.access_token')]];
    };
    $this->requestReturn = fn (Order $order, array $headers, int $quantity, string $reason = 'wrong_size') => $this->postJson(
        "{$this->api}/orders/{$order->public_id}/returns",
        ['lines' => [['order_line_id' => $order->lines()->value('id'), 'quantity' => $quantity]], 'reason_code' => $reason, 'note' => 'Chật quá'],
        $headers,
    );
    $this->onHand = fn () => (int) DB::table('stock_levels')->where('variant_id', $this->s->id)->where('location_id', $this->location->id)->value('on_hand');
    $this->staff = fn (array $permissions = ['admin.access', 'returns.view', 'returns.manage', 'returns.refund']) => $this->actingAs(T::staffFor($this->brand, $permissions), 'staff');
});

it('khách gửi yêu cầu trả 1 sản phẩm; không trả vượt số đã giao', function () {
    [$order, $headers] = ($this->placeAndDeliver)();

    $this->getJson("{$this->api}/orders/{$order->public_id}", $headers)->assertJsonPath('data.returnable.lines.'.$order->lines()->value('id'), 2);

    ($this->requestReturn)($order, $headers, 1)->assertCreated()
        ->assertJsonPath('data.returns.0.status', 'requested')
        ->assertJsonPath('data.returns.0.refund.amount', 300_000)
        ->assertJsonPath('data.returns.0.number', "{$order->number}-R1")
        ->assertJsonPath('data.status.code', 'returning');

    ($this->requestReturn)($order, $headers, 2)->assertStatus(422)->assertJsonPath('error.code', 'return.quantity_exceeded')->assertJsonPath('error.details.allowed', 1);
    expect($order->fresh()->return_status)->toBe('requested');
});

it('không trả được đơn chưa giao hoặc quá hạn; lý do phải hợp lệ', function () {
    [$pending, $pendingHeaders] = ($this->placeAndDeliver)(deliver: false);
    ($this->requestReturn)($pending, $pendingHeaders, 1)->assertStatus(422)->assertJsonPath('error.code', 'return.not_eligible')->assertJsonPath('error.details.reason', 'not_delivered');

    [$order, $headers] = ($this->placeAndDeliver)();
    ($this->requestReturn)($order, $headers, 1, 'khong-co')->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');

    $this->travel(8)->days();
    ($this->requestReturn)($order, $headers, 1)->assertStatus(422)->assertJsonPath('error.details.reason', 'window_expired');
});

it('nhân viên duyệt → nhận hàng (bán được: nhập kho) → hoàn tất + hoàn tiền COD thủ công', function () {
    Event::fake([ReturnResolved::class]);
    [$order, $headers] = ($this->placeAndDeliver)();
    ($this->requestReturn)($order, $headers, 1)->assertCreated();
    $return = ReturnRequest::query()->withoutGlobalScopes()->sole();
    $before = ($this->onHand)();
    ($this->staff)();

    $this->post("{$this->admin}/{$return->id}/resolve", ['amount' => 300_000])->assertSessionHasErrors('business');
    $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'approved', 'lock_version' => $return->lock_version])->assertSessionHasNoErrors();
    $this->post("{$this->admin}/{$return->id}/receive", ['conditions' => []])->assertSessionHasNoErrors();
    expect(($this->onHand)())->toBe($before + 1)
        ->and($order->fresh()->return_status)->toBe('in_progress');

    $this->post("{$this->admin}/{$return->id}/resolve", ['amount' => 400_000])->assertSessionHasErrors('business');
    $this->post("{$this->admin}/{$return->id}/resolve", ['amount' => 300_000, 'note' => 'OK'])->assertSessionHasNoErrors();

    $payment = DB::table('payments')->first();
    expect($return->fresh()->status->value)->toBe('resolved')
        ->and($return->fresh()->refunded_amount)->toBe(300_000)
        ->and((int) $payment->refunded_amount)->toBe(300_000)
        ->and($payment->status)->toBe('partially_refunded')
        ->and(DB::table('refunds')->value('status'))->toBe('requested')
        ->and($order->fresh()->return_status)->toBe('partially_returned')
        ->and($order->fresh()->payment_status)->toBe('partially_refunded');
    Event::assertDispatched(ReturnResolved::class);

    $this->getJson("{$this->api}/orders/{$order->public_id}", $headers)->assertJsonPath('data.returnable.lines.'.$order->lines()->value('id'), 1);
});

it('hàng hư hỏng không nhập kho; hoàn trừ phí; tiền hoàn theo giá sau giảm', function () {
    C::promotion($this->brand, [], ['GIAM10' => 10]);
    [$order, $headers] = ($this->placeAndDeliver)(['GIAM10'], 540_000);
    ($this->requestReturn)($order, $headers, 2)->assertCreated()->assertJsonPath('data.returns.0.refund.amount', 540_000);
    $return = ReturnRequest::query()->withoutGlobalScopes()->sole();
    $before = ($this->onHand)();
    ($this->staff)();

    $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'approved', 'lock_version' => 0]);
    $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'in_transit', 'lock_version' => 1]);
    $lineId = $return->lines()->value('id');
    $this->post("{$this->admin}/{$return->id}/receive", ['conditions' => [$lineId => 'damaged']])->assertSessionHasNoErrors();
    $this->post("{$this->admin}/{$return->id}/resolve", ['amount' => 500_000, 'note' => 'Trừ phí giặt'])->assertSessionHasNoErrors();

    expect(($this->onHand)())->toBe($before)
        ->and($return->lines()->value('condition'))->toBe('damaged')
        ->and($order->fresh()->return_status)->toBe('returned');
});

it('khách huỷ yêu cầu chưa duyệt → trả lại hạn mức; không huỷ được khi đã duyệt', function () {
    [$order, $headers] = ($this->placeAndDeliver)();
    $first = ($this->requestReturn)($order, $headers, 2)->json('data.returns.0.id');

    $this->postJson("{$this->api}/orders/{$order->public_id}/returns/{$first}/cancel", [], $headers)->assertOk()->assertJsonPath('data.returns.0.status', 'cancelled');
    expect($order->fresh()->return_status)->toBe('none');

    $second = ($this->requestReturn)($order, $headers, 2)->assertCreated()->json('data.returns.1.id');
    $return = ReturnRequest::query()->withoutGlobalScopes()->where('public_id', $second)->sole();
    ($this->staff)();
    $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'approved', 'lock_version' => 0]);
    $this->postJson("{$this->api}/orders/{$order->public_id}/returns/{$second}/cancel", [], $headers)->assertStatus(409)->assertJsonPath('error.code', 'return.transition_invalid');
});

it('đơn có yêu cầu đổi/trả đang mở không tự hoàn tất', function () {
    [$order, $headers] = ($this->placeAndDeliver)();
    ($this->requestReturn)($order, $headers, 1)->assertCreated();

    $this->travel(8)->days();
    $this->artisan('vani:orders:complete-delivered')->assertSuccessful();

    expect($order->fresh()->order_status->value)->toBe('processing');
});

it('quyền, khoá lạc quan, panel trên trang đơn', function () {
    [$order, $headers] = ($this->placeAndDeliver)();
    ($this->requestReturn)($order, $headers, 1)->assertCreated();
    $return = ReturnRequest::query()->withoutGlobalScopes()->sole();

    ($this->staff)(['admin.access', 'returns.view', 'orders.view']);
    $this->get("{$this->admin}/{$return->id}")->assertOk();
    $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'approved', 'lock_version' => 0])->assertForbidden();
    $this->get("/admin/orders/lumiere/orders/{$order->id}")->assertInertia(fn ($page) => $page->where('panels', fn ($panels) => collect($panels)->pluck('title')->contains('Đổi/trả')));

    ($this->staff)();
    $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'approved', 'lock_version' => 5])->assertSessionHasErrors('business');
    $this->post("{$this->admin}/{$return->id}/transition", ['to' => 'rejected', 'lock_version' => 0])->assertSessionHasErrors('note');
});
