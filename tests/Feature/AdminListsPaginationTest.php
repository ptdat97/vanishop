<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Contracts\Returns;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| P2 A2 (stabilization-2026-11): danh sách Vận đơn, Thanh toán, Đổi trả trước chỉ hiện 100 bản ghi mới nhất, không phân
| trang — bản ghi cũ không mở được từ danh sách. Nay phân trang server (30/50/100 dòng), giữ bộ lọc, số đơn tra theo lô.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 100);
    // Dựng nhiều đơn nhanh trong một test: nới giới hạn tần suất tạo giỏ/đặt hàng và số lượng mỗi dòng.
    config(['vanishop.rate_limits.cart_create' => 1000, 'vanishop.rate_limits.checkout' => 1000, 'vanishop.cart.max_line_quantity' => 50]);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'fulfillment.view', 'payments.view', 'returns.view'])->create(), 'staff');
});

/** Đặt `$count` đơn COD 1 × S qua Storefront API; trả id đơn. */
function placeOrders(int $count, int $variantId, int $quantity = 1): array
{
    $ids = [];
    for ($i = 0; $i < $count; $i++) {
        $cart = test()->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $cart->json('meta.token')];
        test()->postJson("/api/storefront/v1/carts/{$cart->json('data.id')}/lines", ['variant_id' => $variantId, 'quantity' => $quantity], $headers)->assertOk();
        $total = test()->postJson("/api/storefront/v1/checkout/{$cart->json('data.id')}/quote", C::orderPayload(), $headers)->assertOk()->json('data.total.amount');
        test()->postJson("/api/storefront/v1/checkout/{$cart->json('data.id')}/orders", C::orderPayload(['expected_total' => $total]), [...$headers, 'Idempotency-Key' => "pg-{$i}-{$cart->json('data.id')}"])->assertCreated();
        $ids[] = (int) Order::query()->withoutGlobalScopes()->latest('id')->value('id');
    }

    return $ids;
}

/** Số truy vấn khi mở `$url` — không được tăng theo số dòng trên trang. */
function queriesFor(string $url): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->get($url)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('vận đơn và thanh toán: 31 bản ghi → trang 1 có 30, trang 2 có 1; đổi số dòng; lọc trạng thái giữ khi chuyển trang; số đơn đúng từng dòng', function () {
    $orders = placeOrders(31, $this->s->id);
    $latest = Order::query()->withoutGlobalScopes()->find(end($orders))->number;

    foreach (['/admin/fulfillment/shipments' => 'shipments', '/admin/payment/payments' => 'payments'] as $url => $prop) {
        $this->get($url)->assertInertia(fn (Assert $page) => $page->has($prop, 30)
            ->where("{$prop}.0.order_number", $latest)
            ->where('pagination.total', 31)->where('pagination.last_page', 2));
        $this->get("{$url}?page=2")->assertInertia(fn (Assert $page) => $page->has($prop, 1)->where('pagination.page', 2));
        $this->get("{$url}?per_page=50")->assertInertia(fn (Assert $page) => $page->has($prop, 31)->where('pagination.per_page', 50));
        $this->get("{$url}?per_page=7")->assertInertia(fn (Assert $page) => $page->where('pagination.per_page', 30)); // chỉ nhận 30/50/100
    }
    $this->get('/admin/fulfillment/shipments?status=delivered&page=1')->assertInertia(fn (Assert $page) => $page->has('shipments', 0)->where('pagination.total', 0));
    $this->get('/admin/payment/payments?status=pending')->assertInertia(fn (Assert $page) => $page->where('pagination.total', 31));

    // Số đơn tra theo lô: trang 30 dòng không tốn hơn trang 1 dòng quá vài truy vấn.
    expect(queriesFor('/admin/fulfillment/shipments'))->toBeLessThanOrEqual(queriesFor('/admin/fulfillment/shipments?page=2') + 3)
        ->and(queriesFor('/admin/payment/payments'))->toBeLessThanOrEqual(queriesFor('/admin/payment/payments?page=2') + 3);
});

it('đổi trả: 31 yêu cầu → phân trang 30 + 1, lọc trạng thái', function () {
    [$orderId] = placeOrders(1, $this->s->id, 31);
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($orderId): void {
        $shipment = Shipment::query()->where('order_id', $orderId)->sole();
        app(FulfillmentService::class)->book($shipment->id, 'T1');
        app(FulfillmentService::class)->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'e1', 'staff');
        app(FulfillmentService::class)->updateStatus($shipment->id, ShipmentStatus::Delivered, 'e2', 'staff');
        $lineId = (int) DB::table('order_lines')->where('order_id', $orderId)->value('id');
        for ($i = 0; $i < 31; $i++) {
            app(Returns::class)->request($orderId, [$lineId => 1], 'wrong_size', null, 'customer');
        }
    });

    $this->get('/admin/returns/returns')->assertInertia(fn (Assert $page) => $page->has('returns', 30)->where('pagination.total', 31)->where('counts.requested', 31));
    $this->get('/admin/returns/returns?page=2')->assertInertia(fn (Assert $page) => $page->has('returns', 1));
    $this->get('/admin/returns/returns?status=approved')->assertInertia(fn (Assert $page) => $page->has('returns', 0)->where('pagination.total', 0));
});
