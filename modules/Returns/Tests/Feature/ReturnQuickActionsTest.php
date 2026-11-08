<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Đổi trả ít bước (0.3.102): thao tác ngay trên trang đơn (thẻ Đổi/trả, bước tiếp theo đánh dấu primary) và hàng loạt ở
| danh sách đổi/trả (Duyệt, Đã nhận hàng còn bán được).
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 10);
    $this->deliveredOrder = function (): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 600_000]), [...$headers, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();
        $order = Order::query()->withoutGlobalScopes()->latest('id')->first();
        app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($order) {
            $shipment = Shipment::query()->where('order_id', $order->id)->sole();
            $service = app(FulfillmentService::class);
            $service->book($shipment->id, 'T'.$order->id);
            $service->updateStatus($shipment->id, ShipmentStatus::PickedUp, "p{$order->id}", 'staff');
            $service->updateStatus($shipment->id, ShipmentStatus::Delivered, "d{$order->id}", 'staff');
        });

        return $order->fresh();
    };
    $this->requestReturn = function (Order $order): ReturnRequest {
        $this->post('/admin/returns/returns', ['order_id' => $order->id, 'lines' => [$order->lines()->value('id') => 1], 'reason_code' => 'wrong_size'])->assertRedirect();

        return ReturnRequest::query()->where('order_id', $order->id)->latest('id')->first();
    };
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'returns.view', 'returns.manage', 'returns.refund']), 'staff');
    $this->panel = fn (Order $order): array => collect($this->get("/admin/orders/orders/{$order->id}")->viewData('page')['props']['panels'])->firstWhere('title', 'Đổi/trả');
    $this->primary = fn (Order $order): ?array => collect(($this->panel)($order)['actions'])->firstWhere('primary', true);
    $this->onHand = fn (): int => (int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('on_hand');
});

it('trên trang đơn: Duyệt → Đã nhận hàng (nhập lại kho) → Hoàn tất & hoàn tiền, mỗi bước là "bước tiếp theo"; nhãn tiếng Việt', function () {
    $order = ($this->deliveredOrder)();
    $return = ($this->requestReturn)($order);
    $stock = ($this->onHand)();

    $panel = ($this->panel)($order);
    expect($panel['rows'][0]['value'])->toStartWith('Chờ duyệt · ')
        ->and(collect($panel['actions'])->pluck('label')->all())->toBe(['Duyệt đổi/trả', 'Từ chối']);

    $approve = ($this->primary)($order);
    $this->post($approve['url'], $approve['data'])->assertRedirect();
    expect($return->fresh()->status->value)->toBe('approved')
        ->and(collect(($this->panel)($order)['actions'])->pluck('label')->all())->toBe(['Khách đã gửi hàng', 'Đã nhận hàng (còn bán được)']);

    $receive = ($this->primary)($order);
    $this->post($receive['url'])->assertRedirect();
    expect($return->fresh()->status->value)->toBe('received')->and(($this->onHand)())->toBe($stock + 1);

    $resolve = ($this->primary)($order);
    expect($resolve['label'])->toBe('Hoàn tất & hoàn tiền')->and($resolve['fields'][0]['value'])->toBe((string) $return->refund_amount);
    $this->post($resolve['url'], ['amount' => $return->refund_amount])->assertRedirect();
    expect($return->fresh()->status->value)->toBe('resolved')->and(($this->panel)($order)['actions'])->toBe([]);
});

it('Từ chối cần lý do; không có quyền thì không có thao tác', function () {
    $order = ($this->deliveredOrder)();
    $return = ($this->requestReturn)($order);
    $reject = collect(($this->panel)($order)['actions'])->firstWhere('label', 'Từ chối');
    $this->post($reject['url'], $reject['data'])->assertSessionHasErrors('note');
    $this->post($reject['url'], [...$reject['data'], 'note' => 'Đã quá hạn'])->assertRedirect();
    expect($return->fresh()->status->value)->toBe('rejected');

    $this->actingAs(T::staff(['admin.access', 'orders.view', 'returns.view']), 'staff');
    expect(($this->panel)($order)['actions'])->toBe([]);
});

it('hàng loạt ở danh sách: Duyệt rồi Đã nhận hàng; yêu cầu không đúng trạng thái được bỏ qua', function () {
    $a = ($this->requestReturn)(($this->deliveredOrder)());
    $b = ($this->requestReturn)(($this->deliveredOrder)());
    $stock = ($this->onHand)();

    $actions = collect($this->get('/admin/returns/returns')->viewData('page')['props']['actions'])->pluck('key')->all();
    expect($actions)->toBe(['approve', 'receive']);

    $this->post('/admin/returns/returns/bulk-receive', ['ids' => [$a->id, $b->id]])->assertSessionHas('success', 'Đã nhận hàng 0 yêu cầu (nhập lại kho); bỏ qua 2 yêu cầu chưa duyệt hoặc đã nhận.');
    $this->post('/admin/returns/returns/bulk-approve', ['ids' => [$a->id, $b->id]])->assertSessionHas('success', 'Đã duyệt 2 yêu cầu; bỏ qua 0 yêu cầu không còn chờ duyệt.');
    $this->post('/admin/returns/returns/bulk-receive', ['ids' => [$a->id, $b->id]])->assertSessionHas('success', 'Đã nhận hàng 2 yêu cầu (nhập lại kho); bỏ qua 0 yêu cầu chưa duyệt hoặc đã nhận.');
    expect(ReturnRequest::query()->pluck('status')->map->value->unique()->all())->toBe(['received'])
        ->and(($this->onHand)())->toBe($stock + 2);

    $this->actingAs(T::staff(['admin.access', 'returns.view']), 'staff');
    expect($this->get('/admin/returns/returns')->viewData('page')['props']['actions'])->toBe([]);
    $this->post('/admin/returns/returns/bulk-approve', ['ids' => [$a->id]])->assertForbidden();
});
