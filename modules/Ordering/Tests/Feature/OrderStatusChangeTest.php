<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Application\OrderStatusChanger;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Đổi trạng thái từ ô chọn + hàng loạt (0.3.99): luôn có lý do; theo trình tự → lệnh thường; nhảy tới trước ngoài trình tự
| → quyền orders.override_status, ghi `status_overridden`; tới hoàn tất → huỷ vận đơn mở + trừ hàng đang giữ (tồn khớp).
| Không lùi, không mở lại đơn đã kết thúc, huỷ theo quy tắc cũ.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 20);
    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm_mode', 'off'));
    $this->place = function (): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => 600_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->staff = fn (array $permissions = ['orders.manage', 'orders.cancel', 'orders.override_status']) => $this->actingAs(T::staff(['admin.access', 'orders.view', ...$permissions]), 'staff');
    $this->statusOptions = fn (Order $order): array => collect($this->get("/admin/orders/orders/{$order->id}")->viewData('page')['props']['statusCard']['change']['options'])->keyBy('value')->all();
    $this->stock = fn (): array => (array) DB::table('stock_levels')->where('variant_id', $this->s->id)->first(['on_hand', 'reserved']);
});

it('ô chọn: theo trình tự / ngoài trình tự / bị chặn kèm lý do; ngoài trình tự cần quyền riêng', function () {
    $order = ($this->place)();
    ($this->staff)(['orders.manage', 'orders.cancel']);

    $options = ($this->statusOptions)($order);
    expect($options['confirmed'])->toMatchArray(['mode' => 'normal', 'allowed' => true])
        ->and($options['processing'])->toMatchArray(['mode' => 'forced', 'allowed' => false])
        ->and($options['completed'])->toMatchArray(['mode' => 'forced', 'permission' => 'orders.override_status', 'allowed' => false])
        ->and($options['cancelled'])->toMatchArray(['mode' => 'normal', 'allowed' => true])
        ->and($options['pending']['blocked'])->toBe('Đơn đang ở trạng thái này.');

    $this->post("/admin/orders/orders/{$order->id}/status", ['status' => 'completed', 'reason' => 'x'])->assertForbidden();
    $this->post("/admin/orders/orders/{$order->id}/status", ['status' => 'confirmed'])->assertSessionHasErrors('reason');
    $this->post("/admin/orders/orders/{$order->id}/status", ['status' => 'confirmed', 'reason' => 'Đã gọi khách'])->assertRedirect()
        ->assertSessionHas('success', 'Đã đổi trạng thái đơn.');
    expect(DB::table('order_events')->where('order_id', $order->id)->where('to_status', 'confirmed')->value('reason'))->toBe('staff_changed: Đã gọi khách');

    // Đã xác nhận → tự tạo vận đơn → đang xử lý; lùi về "đã xác nhận" bị chặn.
    expect(($this->statusOptions)($order)['confirmed']['blocked'])->toBe('Không lùi trạng thái đơn (tồn kho, thanh toán đã xử lý theo bước sau).');
    $this->post("/admin/orders/orders/{$order->id}/status", ['status' => 'pending', 'reason' => 'x'])->assertSessionHasErrors('business');
});

it('ngoài trình tự: chờ xác nhận → hoàn tất (giao ngoài hệ thống) — ghi lịch sử + audit, chốt hàng đang giữ, tồn kho khớp; không mở lại được', function () {
    $order = ($this->place)();
    ($this->staff)();
    expect(($this->stock)())->toMatchArray(['on_hand' => 20, 'reserved' => 2]);

    $this->post("/admin/orders/orders/{$order->id}/status", ['status' => 'completed', 'reason' => 'Khách lấy tại cửa hàng'])->assertRedirect()
        ->assertSessionHas('success', 'Đã đổi trạng thái đơn ngoài trình tự (ghi vào lịch sử).');

    $event = DB::table('order_events')->where('order_id', $order->id)->where('type', 'status_overridden')->sole();
    expect($order->fresh()->order_status->value)->toBe('completed')
        ->and([$event->from_status, $event->to_status, $event->reason])->toBe(['pending', 'completed', 'override: Khách lấy tại cửa hàng'])
        ->and(DB::table('audit_logs')->where('action', 'order.status_overridden')->where('subject_id', (string) $order->id)->exists())->toBeTrue()
        ->and(($this->stock)())->toMatchArray(['on_hand' => 18, 'reserved' => 0]);
    $this->artisan('vani:inventory:verify')->assertSuccessful();

    expect(($this->statusOptions)($order)['processing']['blocked'])->toBe('Đơn đã hoàn tất hoặc đã huỷ — không mở lại được.');
});

it('ngoài trình tự khi đã có vận đơn: hoàn tất → huỷ vận đơn chưa rời kho; chờ xác nhận → đang xử lý vẫn tạo vận đơn như thường', function () {
    $confirmed = ($this->place)();
    ($this->staff)();
    $this->post("/admin/orders/orders/{$confirmed->id}/status", ['status' => 'confirmed', 'reason' => 'Đã gọi'])->assertRedirect();
    expect(Shipment::query()->where('order_id', $confirmed->id)->sole()->status->value)->toBe('pending_booking');

    $this->post("/admin/orders/orders/{$confirmed->id}/status", ['status' => 'completed', 'reason' => 'Giao tay'])->assertRedirect();
    expect(Shipment::query()->where('order_id', $confirmed->id)->sole()->status->value)->toBe('cancelled')
        ->and($confirmed->fresh()->order_status->value)->toBe('completed');

    $skipped = ($this->place)();
    $this->post("/admin/orders/orders/{$skipped->id}/status", ['status' => 'processing', 'reason' => 'Đã chốt qua Pancake'])->assertRedirect();
    expect($skipped->fresh()->order_status->value)->toBe('processing')
        ->and(Shipment::query()->where('order_id', $skipped->id)->sole()->status->value)->toBe('pending_booking');
    $this->artisan('vani:inventory:verify')->assertSuccessful();
});

it('hàng loạt: mỗi đơn đi đúng đường; đơn bị chặn hoặc thiếu quyền bị bỏ qua; lý do bắt buộc', function () {
    [$a, $b, $c] = [($this->place)(), ($this->place)(), ($this->place)()];
    ($this->staff)(['orders.manage', 'orders.cancel']);
    $this->post("/admin/orders/orders/{$c->id}/status", ['status' => 'cancelled', 'reason' => 'Khách huỷ'])->assertRedirect();

    $props = $this->get('/admin/orders/orders')->viewData('page')['props'];
    $action = collect($props['extensions']['actions'])->firstWhere('key', 'status');
    expect(collect($action['fields'])->pluck('key')->all())->toBe(['status', 'reason'])
        ->and(collect($props['bulkStatus']['options'])->pluck('value')->all())->toBe(['confirmed', 'processing', 'completed', 'cancelled']);

    $this->post('/admin/orders/orders/bulk-status', ['ids' => [$a->id, $b->id, $c->id], 'status' => 'confirmed'])->assertSessionHasErrors('reason');
    $this->post('/admin/orders/orders/bulk-status', ['ids' => [$a->id, $b->id, $c->id], 'status' => 'confirmed', 'reason' => 'Gọi xong'])->assertRedirect()
        ->assertSessionHas('success', 'Đã đổi trạng thái 2 đơn; bỏ qua 1 đơn.');

    // Ngoài trình tự (đang xử lý → hoàn tất khi chưa giao) cần quyền riêng.
    $this->post('/admin/orders/orders/bulk-status', ['ids' => [$a->id, $b->id], 'status' => 'completed', 'reason' => 'Giao tay'])
        ->assertSessionHas('success', 'Đã đổi trạng thái 0 đơn; bỏ qua 2 đơn.');
    ($this->staff)();
    $this->post('/admin/orders/orders/bulk-status', ['ids' => [$a->id, $b->id], 'status' => 'completed', 'reason' => 'Giao tay'])
        ->assertSessionHas('success', 'Đã đổi trạng thái 2 đơn; bỏ qua 0 đơn.');
    expect(Order::query()->whereIn('id', [$a->id, $b->id])->pluck('order_status')->map->value->unique()->all())->toBe(['completed']);
    $this->artisan('vani:inventory:verify')->assertSuccessful();
});

it('chỉ nhận mã lý do phù hợp và lưu phân loại ở cả xác nhận và huỷ đơn', function () {
    [$confirmed, $cancelled] = [($this->place)(), ($this->place)()];
    T::seed(fn () => app(Settings::class)->set('core', 'orders.cancellation_reasons', 'bad_address|Sai địa chỉ'));
    ($this->staff)();

    $options = ($this->statusOptions)($confirmed);
    expect(collect($options['confirmed']['reasons'])->pluck('value')->all())->toContain('customer_confirmed')
        ->and(collect($options['cancelled']['reasons'])->pluck('value')->all())->toContain('bad_address');
    $this->post("/admin/orders/orders/{$confirmed->id}/status", ['status' => 'confirmed', 'reason_code' => 'bad_address'])
        ->assertSessionHasErrors(['reason_code' => 'Lý do đã chọn không phù hợp với trạng thái mới.']);
    expect($confirmed->fresh()->order_status->value)->toBe('pending');

    $this->post("/admin/orders/orders/{$confirmed->id}/status", ['status' => 'confirmed', 'reason_code' => 'customer_confirmed'])->assertSessionHasNoErrors();
    $this->post("/admin/orders/orders/{$cancelled->id}/status", ['status' => 'cancelled', 'reason_code' => 'bad_address', 'reason' => 'Địa chỉ ngoài vùng giao'])->assertSessionHasNoErrors();

    $confirmedEvent = DB::table('order_events')->where('order_id', $confirmed->id)->where('to_status', 'confirmed')->sole();
    $cancelledEvent = DB::table('order_events')->where('order_id', $cancelled->id)->where('to_status', 'cancelled')->sole();
    expect($confirmedEvent->reason)->toBe('staff_changed: Khách đã xác nhận')
        ->and(json_decode($confirmedEvent->data, true))->toBe(['reason_code' => 'customer_confirmed'])
        ->and($cancelledEvent->reason)->toBe('Địa chỉ ngoài vùng giao')
        ->and(json_decode($cancelledEvent->data, true))->toBe(['reason_code' => 'bad_address']);
});

it('đổi hàng loạt kiểm tra mã một lần trước khi ghi và lưu mã trên từng đơn', function () {
    [$first, $second] = [($this->place)(), ($this->place)()];
    ($this->staff)();
    $bulk = ['ids' => [$first->id, $second->id], 'status' => 'cancelled'];
    $this->post('/admin/orders/orders/bulk-status', [...$bulk, 'reason_code' => 'customer_confirmed'])->assertSessionHasErrors('reason_code');
    expect($first->fresh()->order_status->value)->toBe('pending')->and($second->fresh()->order_status->value)->toBe('pending');

    $this->post('/admin/orders/orders/bulk-status', [...$bulk, 'reason_code' => 'out_of_stock'])
        ->assertSessionHas('success', 'Đã đổi trạng thái 2 đơn; bỏ qua 0 đơn.');
    $events = DB::table('order_events')->whereIn('order_id', $bulk['ids'])->where('to_status', 'cancelled')->get();
    expect($events)->toHaveCount(2);
    foreach ($events as $event) {
        expect($event->reason)->toBe('Hết hàng')->and(json_decode($event->data, true))->toBe(['reason_code' => 'out_of_stock']);
    }
});

it('mã lý do ngoài trình tự vẫn cần ghi chú ở cả thao tác riêng lẻ và hàng loạt', function () {
    [$first, $second] = [($this->place)(), ($this->place)()];
    ($this->staff)();
    $override = ['status' => 'completed', 'reason_code' => 'store_pickup'];
    $error = ['reason' => 'Nhập ghi chú khi đổi trạng thái ngoài trình tự.'];
    $this->post("/admin/orders/orders/{$first->id}/status", $override)->assertSessionHasErrors($error);
    $bulk = [...$override, 'ids' => [$first->id, $second->id]];
    $this->post('/admin/orders/orders/bulk-status', $bulk)->assertSessionHasErrors($error);
    expect($first->fresh()->order_status->value)->toBe('pending')->and($second->fresh()->order_status->value)->toBe('pending');

    $this->post('/admin/orders/orders/bulk-status', [...$bulk, 'reason' => 'Đã đối chiếu phiếu nhận tại quầy'])
        ->assertSessionHas('success', 'Đã đổi trạng thái 2 đơn; bỏ qua 0 đơn.');
    $event = DB::table('order_events')->where('order_id', $first->id)->where('type', 'status_overridden')->sole();
    expect($event->reason)->toBe('override: Đã đối chiếu phiếu nhận tại quầy')
        ->and(json_decode($event->data, true))->toBe(['reason_code' => 'store_pickup']);
});

it('ngoài trình tự cần ghi chú ngay tại service, không chỉ ở controller: mã lý do thôi chưa đủ', function () {
    $order = ($this->place)();
    $changer = app(OrderStatusChanger::class);

    expect(fn () => $changer->change($order, OrderStatus::Completed, null))->toThrow(ValidationException::class)
        ->and(fn () => $changer->change($order, OrderStatus::Completed, '   '))->toThrow(ValidationException::class)
        ->and($order->fresh()->order_status->value)->toBe('pending')
        ->and($changer->change($order, OrderStatus::Completed, 'Khách lấy tại cửa hàng'))->toBe('forced')
        ->and($order->fresh()->order_status->value)->toBe('completed');
});
