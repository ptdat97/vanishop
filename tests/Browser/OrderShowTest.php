<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Application\OrderStatusChanger;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Trang đơn Admin trên trình duyệt thật: lỗi JS lúc render (vd. "Cannot access … before initialization") làm trang trắng
| mà test phía server (props Inertia) không bắt được.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->place = function (): Order {
        $s = $this->s;
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    };
});

it('trang đơn mở được: bước tiếp theo, nhãn rủi ro khách, không lỗi JS', function () {
    $s = $this->s;
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $s->id, 'quantity' => 1], $headers)->assertOk();
    $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-browser'])->assertCreated();
    $order = Order::query()->withoutGlobalScopes()->sole();
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', 'fulfillment.view', 'payments.view']), 'staff');

    visit("/admin/orders/orders/{$order->id}")->assertSee("Đơn {$order->number}")->assertSee('Xác nhận đơn')->assertSee('Khách mới')->assertNoJavaScriptErrors();
});

it('đơn hoàn tất ngoài trình tự (vận đơn huỷ, COD chưa thu, có lịch sử đổi trạng thái): trang mở được, không lỗi JS', function () {
    C::autoConfirmCod();
    $order = ($this->place)();
    T::seed(fn () => app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(OrderStatusChanger::class)->change($order->fresh(), OrderStatus::Completed, 'khách nhận tại cửa hàng')));
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', 'orders.override_status', 'fulfillment.view', 'fulfillment.manage', 'payments.view', 'payments.confirm', 'payments.refund']), 'staff');

    visit("/admin/orders/orders/{$order->id}")->assertSee("Đơn {$order->number}")->assertSee('Đã thu tiền')->assertNoJavaScriptErrors();
});
