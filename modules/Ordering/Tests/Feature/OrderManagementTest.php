<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    config(['vanishop.payment.bank_transfer.accounts.default' => ['bank' => 'VCB', 'account_number' => '0123456789', 'account_name' => 'VANI']]);
    ['brand' => $this->brand, 'channel' => $this->channel, 's' => $this->s] = C::store();
    $this->api = '/api/storefront/v1';
    $this->headers = ['X-Vani-Channel' => 'web-lumiere'];
    $this->place = function (string $method = 'cod', array $overrides = []) {
        $created = $this->postJson("{$this->api}/carts", [], $this->headers)->assertCreated();
        $headers = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 330_000, ...$overrides]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();
    };
    $this->order = fn () => Order::query()->withoutGlobalScopes()->latest('id')->first();
    $this->reserved = fn () => (int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('reserved');
    $this->admin = '/admin/orders/lumiere/orders';
    $this->staff = fn (array $permissions = ['admin.access', 'orders.view', 'orders.manage', 'orders.cancel', 'payments.view']) => $this->actingAs(T::staffFor($this->brand, $permissions), 'staff');
});

it('Admin: danh sách, lọc theo trạng thái, tìm theo số đơn hoặc SĐT', function () {
    ($this->place)();
    ($this->place)('cod', ['contact' => ['phone' => '0987 000 111']]);
    $first = Order::query()->withoutGlobalScopes()->orderBy('id')->first();
    ($this->staff)();

    $this->get($this->admin)->assertInertia(fn (Assert $page) => $page->component('Ordering::Orders/Index')->has('orders', 2)->where('pagination.total', 2));
    $this->get("{$this->admin}?q=".strtolower($first->number))->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.number', $first->number));
    $this->get("{$this->admin}?q=0987000111")->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.phone', '+84987000111'));
    $this->get("{$this->admin}?status=cancelled")->assertInertia(fn (Assert $page) => $page->has('orders', 0));
});

it('Admin: chi tiết đơn có panel thanh toán do module Payment cung cấp', function () {
    ($this->place)();
    ($this->staff)();

    $this->get("{$this->admin}/".($this->order)()->id)->assertInertia(fn (Assert $page) => $page->component('Ordering::Orders/Show')
        ->where('order.number', ($this->order)()->number)
        ->where('order.customerStatus.code', 'preparing')
        ->where('panels.0.title', 'Thanh toán')
        ->where('can.cancel', true)
        ->where('can.confirm', false));
});

it('Admin: xác nhận đơn COD khi tắt tự xác nhận', function () {
    config(['vanishop.payment.cod.auto_confirm' => false]);
    ($this->place)();
    ($this->staff)();
    $order = ($this->order)();

    $this->post("{$this->admin}/{$order->id}/confirm")->assertSessionHasNoErrors();

    expect($order->fresh()->order_status->value)->toBe('confirmed')
        ->and(DB::table('order_events')->where('order_id', $order->id)->where('to_status', 'confirmed')->value('source'))->toBe('staff')
        ->and(DB::table('audit_logs')->where('action', 'order.confirmed')->exists())->toBeTrue();
});

it('Admin huỷ đơn đã thanh toán: nhả hàng, hoàn lượt voucher, tạo yêu cầu hoàn tiền', function () {
    C::promotion($this->brand, [], ['GIAM10' => 5]);
    ($this->place)('manual_bank_transfer', ['voucher_codes' => ['GIAM10'], 'expected_total' => 300_000]);
    $order = ($this->order)();
    ($this->staff)(['admin.access', 'orders.view', 'orders.cancel', 'payments.view', 'payments.confirm']);
    $paymentId = DB::table('payments')->value('id');
    $this->post("/admin/payment/lumiere/payments/{$paymentId}/confirm", ['note' => 'VCB'])->assertSessionHasNoErrors();

    $this->post("{$this->admin}/{$order->id}/cancel", ['reason' => 'Khách đổi ý'])->assertSessionHasNoErrors();

    expect($order->fresh()->order_status->value)->toBe('cancelled')
        ->and(($this->reserved)())->toBe(0)
        ->and(DB::table('vouchers')->value('used_count'))->toEqual(0)
        ->and(DB::table('refunds')->value('status'))->toBe('requested')
        ->and((int) DB::table('refunds')->value('amount'))->toBe(300_000)
        ->and($order->fresh()->payment_status)->toBe('refunded');
});

it('Admin: không huỷ được đơn đã xuất kho; không có quyền thì 403', function () {
    ($this->place)();
    $order = ($this->order)();
    ($this->staff)(['admin.access', 'orders.view']);
    $this->post("{$this->admin}/{$order->id}/cancel", ['reason' => 'x'])->assertForbidden();

    ($this->staff)();
    DB::table('orders')->where('id', $order->id)->update(['order_status' => 'processing', 'fulfillment_status' => 'shipped']);
    $this->post("{$this->admin}/{$order->id}/cancel", ['reason' => 'x'])->assertSessionHasErrors('business');
    expect($order->fresh()->order_status->value)->toBe('processing');
});

it('Admin: đổi địa chỉ trước khi giao (lưu địa chỉ cũ trong lịch sử), khoá lạc quan, ghi chú', function () {
    ($this->place)();
    $order = ($this->order)();
    ($this->staff)();
    $address = ['province_code' => '01', 'province_name' => 'Hà Nội', 'ward_code' => '00004', 'ward_name' => 'Phường Ba Đình', 'street_line' => '1 Hoàng Diệu', 'reason' => 'Khách gọi đổi'];

    $this->put("{$this->admin}/{$order->id}/shipping-address", [...$address, 'lock_version' => $order->lock_version])->assertSessionHasNoErrors();
    $this->put("{$this->admin}/{$order->id}/shipping-address", [...$address, 'lock_version' => $order->lock_version])->assertSessionHasErrors('business');
    $this->post("{$this->admin}/{$order->id}/notes", ['note' => 'Giao sau 17h'])->assertSessionHasNoErrors();

    $event = DB::table('order_events')->where('order_id', $order->id)->where('type', 'address_changed')->first();
    expect($order->fresh()->shipping_address['province_name'])->toBe('Hà Nội')
        ->and(json_decode($event->data, true)['from']['province_name'])->toBe('TP. Hồ Chí Minh')
        ->and(DB::table('order_events')->where('type', 'note')->exists())->toBeTrue();

    DB::table('orders')->where('id', $order->id)->update(['fulfillment_status' => 'shipped']);
    $this->put("{$this->admin}/{$order->id}/shipping-address", [...$address, 'lock_version' => $order->fresh()->lock_version])->assertSessionHasErrors('business');
});

it('cô lập brand: không xem được đơn brand khác', function () {
    ($this->place)();
    $order = ($this->order)();
    $other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->actingAs(T::staffFor($other, ['admin.access', 'orders.view']), 'staff');

    $this->get("/admin/orders/urbanx/orders/{$order->id}")->assertNotFound();
    $this->get("{$this->admin}/{$order->id}")->assertNotFound();
});

it('snapshot: đổi tên, giá, ngừng bán sản phẩm không làm đổi đơn cũ', function () {
    $token = ($this->place)()->json('data.access_token');
    $order = ($this->order)();

    T::seed(function () {
        DB::table('style_translations')->update(['name' => 'Tên mới']);
        DB::table('prices')->update(['amount' => 999_000]);
        $this->s->update(['status' => 'inactive', 'sku' => 'SKU-DOI']);
    });

    $this->getJson("{$this->api}/orders/{$order->public_id}", [...$this->headers, 'X-Vani-Order-Token' => $token])->assertOk()
        ->assertJsonPath('data.lines.0.name', 'Đầm lụa')
        ->assertJsonPath('data.lines.0.sku', $order->lines()->value('sku'))
        ->assertJsonPath('data.lines.0.unit_price.amount', 300_000)
        ->assertJsonPath('data.total.amount', 330_000);
    ($this->staff)();
    $this->get("{$this->admin}/{$order->id}")->assertInertia(fn (Assert $page) => $page->where('order.lines.0.name', 'Đầm lụa')->where('order.lines.0.unit_amount', 300_000));
});

it('khách: tra cứu bằng số đơn + SĐT (thông tin bị che), xem bằng token, huỷ đơn', function () {
    $token = ($this->place)()->json('data.access_token');
    $order = ($this->order)();
    expect($token)->toHaveLength(40);

    $this->getJson("{$this->api}/orders/track?number={$order->number}&phone=0912345678", $this->headers)->assertOk()
        ->assertJsonPath('data.number', $order->number)
        ->assertJsonPath('data.status.code', 'preparing')
        ->assertJsonPath('data.customer.full_name', 'N. T. Lan')
        ->assertJsonPath('data.shipping_address.street_line', '***');
    $this->getJson("{$this->api}/orders/track?number={$order->number}&phone=0900000000", $this->headers)->assertNotFound();
    $this->getJson("{$this->api}/orders/{$order->public_id}", [...$this->headers, 'X-Vani-Order-Token' => 'sai'])->assertNotFound();

    $this->postJson("{$this->api}/orders/{$order->public_id}/cancel", ['reason' => 'Đặt nhầm size'], [...$this->headers, 'X-Vani-Order-Token' => $token])->assertOk()
        ->assertJsonPath('data.status.code', 'cancelled')
        ->assertJsonPath('data.can_cancel', false);
    expect(($this->reserved)())->toBe(0)
        ->and(DB::table('order_events')->where('to_status', 'cancelled')->value('source'))->toBe('customer');

    $this->postJson("{$this->api}/orders/{$order->public_id}/cancel", ['reason' => 'lần 2'], [...$this->headers, 'X-Vani-Order-Token' => $token])
        ->assertStatus(409)->assertJsonPath('error.code', 'order.cannot_cancel');
});

it('khách không huỷ được đơn đang xử lý kho', function () {
    $token = ($this->place)()->json('data.access_token');
    $order = ($this->order)();
    DB::table('orders')->where('id', $order->id)->update(['order_status' => 'processing']);

    $this->postJson("{$this->api}/orders/{$order->public_id}/cancel", ['reason' => 'x'], [...$this->headers, 'X-Vani-Order-Token' => $token])
        ->assertStatus(409)->assertJsonPath('error.code', 'order.cannot_cancel');
});

it('tra cứu đơn bị giới hạn tần suất', function () {
    foreach (range(1, 10) as $ignored) {
        $this->getJson("{$this->api}/orders/track?number=X&phone=0912345678", $this->headers)->assertNotFound();
    }
    $this->getJson("{$this->api}/orders/track?number=X&phone=0912345678", $this->headers)->assertStatus(429);
});
