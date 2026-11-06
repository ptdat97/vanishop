<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Persistence\Models\Payment;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Trang đơn Admin là chỗ làm việc (0.3.96): thẻ Giao hàng / Thanh toán có thao tác theo trạng thái (bước tiếp theo đánh dấu
| `primary`), giai đoạn vận hành cho thanh tiến trình, lịch sử gộp sự kiện vận đơn + thanh toán.
*/

beforeEach(function () {
    config(['vani.bank-transfer.account' => ['bank' => 'VCB', 'account_number' => '0123456789', 'account_name' => 'VANI']]);
    ['s' => $this->s] = C::store(stock: 20);
    $this->place = function (string $method = 'cod'): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->staff = fn (array $extra = ['fulfillment.view', 'fulfillment.manage', 'payments.view', 'payments.confirm', 'payments.refund']) => $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', ...$extra]), 'staff');
    $this->page = fn (Order $order): array => $this->get("/admin/orders/orders/{$order->id}")->assertOk()->viewData('page')['props'];
});

it('giao hàng ngay trên trang đơn: nhập mã → đã bàn giao → đã giao; mỗi bước là "bước tiếp theo"; lịch sử gộp sự kiện vận đơn', function () {
    // COD tự xác nhận (mặc định của vani.cod) → Fulfillment tự tạo vận đơn chờ nhập mã.
    $order = ($this->place)();
    ($this->staff)();

    $props = ($this->page)($order);
    $shipping = collect($props['panels'])->firstWhere('title', 'Giao hàng');
    $primary = collect($shipping['actions'])->firstWhere('primary', true);
    expect($props['stage'])->toBe('to_ship')
        ->and($shipping['rows'][0]['value'])->toBe('chưa có mã · Chờ đặt vận đơn')
        ->and($primary['label'])->toBe('Nhập mã vận đơn')
        ->and(array_column($primary['fields'], 'key'))->toBe(['tracking_number', 'service_code'])
        ->and(collect($props['timeline'])->pluck('title'))->toContain('Vận đơn: Chờ đặt vận đơn')
        ->and(collect($props['order']['events'])->every(fn (array $event): bool => $event['ts'] > 0))->toBeTrue();

    $this->post($primary['url'], ['tracking_number' => 'JT0001', 'service_code' => 'J&T'])->assertRedirect();
    $primary = collect(collect(($this->page)($order)['panels'])->firstWhere('title', 'Giao hàng')['actions'])->firstWhere('primary', true);
    expect($primary['label'])->toBe('Đã bàn giao cho hãng');

    $this->post($primary['url'], $primary['data'])->assertRedirect();
    $props = ($this->page)($order);
    $primary = collect(collect($props['panels'])->firstWhere('title', 'Giao hàng')['actions'])->firstWhere('primary', true);
    expect($props['stage'])->toBe('shipping')->and($primary['label'])->toBe('Đã giao')
        ->and(collect(collect($props['panels'])->firstWhere('title', 'Giao hàng')['actions'])->pluck('label')->all())->toBe(['Đã giao', 'Giao không thành công', 'Đang hoàn về']);

    $this->post($primary['url'], $primary['data'])->assertRedirect();
    $props = ($this->page)($order);
    expect($props['stage'])->toBe('delivered')
        ->and(Shipment::query()->sole()->status->value)->toBe('delivered')
        ->and(collect($props['timeline'])->pluck('title')->all())->toContain('Vận đơn: Đã bàn giao hãng', 'Vận đơn: Đã giao')
        // COD thu hộ → thanh toán ghi nhận đã thu, hiện trong lịch sử.
        ->and(collect($props['timeline'])->pluck('title')->all())->toContain('Thanh toán: Đã thu COD');
});

it('đơn chuyển khoản: "Xác nhận đã nhận tiền" là bước tiếp theo trên thẻ Thanh toán → đơn sang chờ giao hãng', function () {
    $order = ($this->place)('manual_bank_transfer');
    ($this->staff)();

    $props = ($this->page)($order);
    $payment = collect($props['panels'])->firstWhere('title', 'Thanh toán');
    $confirm = collect($payment['actions'])->firstWhere('primary', true);
    expect($props['stage'])->toBe('awaiting_payment')
        ->and($payment['rows'][0]['value'])->toStartWith('Chờ thanh toán · ')
        ->and($confirm['label'])->toBe('Xác nhận đã nhận tiền');

    $this->post($confirm['url'], ['note' => 'VCB FT123'])->assertRedirect();
    expect(Payment::query()->sole()->status->value)->toBe('paid')
        ->and(($this->page)($order)['stage'])->toBe('to_ship');
});

it('phân quyền: chỉ xem đơn vẫn thấy thẻ và lịch sử giao hàng/thanh toán, nhưng không có thao tác', function () {
    $order = ($this->place)();
    ($this->staff)(['fulfillment.view', 'payments.view']);
    $props = ($this->page)($order);
    expect(collect($props['panels'])->firstWhere('title', 'Giao hàng')['actions'])->toBe([])
        ->and(collect($props['panels'])->firstWhere('title', 'Thanh toán')['actions'])->toBe([]);

    ($this->staff)([]);
    $props = ($this->page)($order);
    expect(collect($props['panels'])->firstWhere('title', 'Giao hàng')['actions'])->toBe([])
        ->and(collect($props['timeline'])->pluck('title'))->toContain('Vận đơn: Chờ đặt vận đơn');
});
