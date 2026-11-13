<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Danh sách đơn theo hàng đợi việc (0.3.96): giai đoạn vận hành tính từ 4 chiều trạng thái, thao tác hàng loạt của Ordering
| (xác nhận) và Fulfillment (tạo vận đơn, đã bàn giao) qua slot.
*/

beforeEach(function () {
    config(['vani.bank-transfer.account' => ['bank' => 'VCB', 'account_number' => '0123456789', 'account_name' => 'VANI']]);
    ['s' => $this->s] = C::store(stock: 50);
    // CSKH gọi xác nhận: đơn COD dừng ở "Cần xác nhận".
    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm_mode', 'off'));
    $this->place = function (string $method = 'cod'): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 600_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', 'fulfillment.view', 'fulfillment.manage']), 'staff');
    $this->url = '/admin/orders/orders';
});

it('thẻ hàng đợi: số đếm và lọc theo giai đoạn vận hành; cột sản phẩm, tuổi đơn, tỉnh; thao tác hàng loạt của Ordering và Fulfillment', function () {
    [$a, $b] = [($this->place)(), ($this->place)()];
    $transfer = ($this->place)('manual_bank_transfer');
    $shipping = ($this->place)();
    $returned = ($this->place)();
    DB::table('orders')->where('id', $shipping->id)->update(['order_status' => 'processing', 'fulfillment_status' => 'shipped']);
    DB::table('orders')->where('id', $returned->id)->update(['order_status' => 'processing', 'fulfillment_status' => 'returned_to_sender']);

    $this->get($this->url)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Ordering::Orders/Index')
        ->where('stageCounts.to_confirm', 2)
        ->where('stageCounts.awaiting_payment', 1)
        ->where('stageCounts.shipping', 1)
        ->where('stageCounts.issue', 1)
        ->where('labels.stage.to_ship', 'Chờ giao hãng')
        // Nhãn cách thanh toán do Payment cung cấp (cổng đang cài), Ordering không ghi cứng mã cổng.
        ->where('labels.payment_method.cod', fn (string $label): bool => $label !== 'cod' && $label !== '')
        ->where('orders.0.items.0.quantity', 2)
        ->where('pagination.from', 1)
        ->where('pagination.to', 5)
        ->where('orders.0.province', 'Thành phố Hồ Chí Minh')
        ->where('orders.0.age_hours', 0)
        ->where('extensions.actions', fn ($actions) => collect($actions)->pluck('key')->all() === ['confirm', 'print', 'status', 'create_shipments', 'hand_over', 'export'])
        ->where('extensions.columns.0.label', 'Vận đơn')
        ->where('tools.0.key', 'import_tracking'));

    $this->get("{$this->url}?stage=to_confirm")->assertInertia(fn (Assert $page) => $page->has('orders', 2)
        ->where('orders.0.stage', 'to_confirm')->where('filters.stage', 'to_confirm')
        // Số trên thẻ không đổi theo thẻ đang mở.
        ->where('stageCounts.awaiting_payment', 1));
    $this->get("{$this->url}?stage=awaiting_payment")->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.id', $transfer->id));

    // Xác nhận hàng loạt: đơn không còn chờ xác nhận (đang giao) bị bỏ qua. Xác nhận → Fulfillment tự tạo vận đơn → Chờ giao hãng.
    $this->post("{$this->url}/bulk-confirm", ['ids' => [$a->id, $b->id, $shipping->id]])->assertRedirect()
        ->assertSessionHas('success', 'Đã xác nhận 2 đơn; bỏ qua 1 đơn không còn chờ xác nhận.');
    $this->get("{$this->url}?stage=to_ship")->assertInertia(fn (Assert $page) => $page->has('orders', 2)
        ->where("extensions.values.{$a->id}.shipments", 'Chưa có mã — Chờ đặt vận đơn'));
});

it('Fulfillment hàng loạt: tạo vận đơn cho đơn đã xác nhận chưa có vận đơn; đã bàn giao chỉ với vận đơn đã có mã → đơn sang Đang giao', function () {
    config(['vanishop.fulfillment.auto_create' => false]);
    [$a, $b] = [($this->place)(), ($this->place)()];
    $this->post("{$this->url}/bulk-confirm", ['ids' => [$a->id, $b->id]])->assertRedirect();
    expect(Shipment::query()->count())->toBe(0);

    $pending = ($this->place)();
    $this->post('/admin/fulfillment/shipments/bulk-create', ['ids' => [$a->id, $b->id, $pending->id]])->assertRedirect()
        ->assertSessionHas('success', 'Đã tạo vận đơn cho 2 đơn; bỏ qua 1 đơn chưa xác nhận hoặc đã có vận đơn.');
    expect(Shipment::query()->count())->toBe(2);

    $shipment = Shipment::query()->where('order_id', $a->id)->sole();
    $this->post("/admin/fulfillment/shipments/{$shipment->id}/book", ['tracking_number' => 'GHN123', 'service_code' => 'GHN'])->assertRedirect();
    $this->post('/admin/fulfillment/shipments/bulk-hand-over', ['ids' => [$a->id, $b->id]])->assertRedirect()
        ->assertSessionHas('success', 'Đã ghi nhận bàn giao 1 vận đơn cho hãng.');

    expect($shipment->fresh()->status->value)->toBe('picked_up');
    $this->get("{$this->url}?stage=shipping")->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.id', $a->id)
        ->where("extensions.values.{$a->id}.shipments", 'GHN · GHN123 — Đã bàn giao hãng'));

    // Chỉ xem đơn: vẫn thấy cột Vận đơn, chỉ in phiếu được; không có thao tác ghi, không có công cụ nhập file.
    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    $this->get($this->url)->assertInertia(fn (Assert $page) => $page->where('extensions.actions', fn ($actions) => collect($actions)->pluck('key')->all() === ['print'])
        ->where('extensions.columns.0.label', 'Vận đơn')->where('tools', []));
    $this->post('/admin/fulfillment/shipments/bulk-create', ['ids' => [$b->id]])->assertForbidden();
});

it('lọc theo ngày đặt (giờ cửa hàng), nguồn và cách thanh toán', function () {
    $old = ($this->place)();
    DB::table('orders')->where('id', $old->id)->update(['placed_at' => now()->subDays(10)]);
    $today = ($this->place)('manual_bank_transfer');
    $day = now()->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d');

    $this->get("{$this->url}?from={$day}&to={$day}")->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.id', $today->id)->where('stageCounts.to_confirm', 0));
    $this->get("{$this->url}?payment_method=cod")->assertInertia(fn (Assert $page) => $page->has('orders', 1)->where('orders.0.id', $old->id));
    $this->get("{$this->url}?source=admin")->assertInertia(fn (Assert $page) => $page->has('orders', 0));
});

it('ưu tiên đơn quá hạn lâu nhất trước mà không đổi thứ tự danh sách thông thường', function () {
    $oldest = ($this->place)();
    $newest = ($this->place)();
    DB::table('orders')->where('id', $oldest->id)->update(['placed_at' => now()->subDays(4)]);
    DB::table('orders')->where('id', $newest->id)->update(['placed_at' => now()->subDays(2)]);

    $this->get("{$this->url}?aged=24")->assertInertia(fn (Assert $page) => $page
        ->where('agedCount', 2)
        ->where('orders.0.id', $oldest->id)
        ->where('orders.1.id', $newest->id));
    $this->get($this->url)->assertInertia(fn (Assert $page) => $page
        ->where('orders.0.id', $newest->id)
        ->where('orders.1.id', $oldest->id));
    $this->get("{$this->url}?actionable=1")->assertInertia(fn (Assert $page) => $page
        ->where('filters.actionable', '1')
        ->where('orders.0.id', $oldest->id)
        ->where('orders.1.id', $newest->id));
});

it('danh sách đơn sắp theo tổng tiền khi bấm tiêu đề cột; mặc định mới nhất trước', function () {
    [$first, $second, $third] = [($this->place)(), ($this->place)(), ($this->place)()];
    DB::table('orders')->where('id', $first->id)->update(['total_amount' => 900_000]);
    DB::table('orders')->where('id', $second->id)->update(['total_amount' => 100_000]);
    DB::table('orders')->where('id', $third->id)->update(['total_amount' => 500_000]);
    $numbers = fn (string $query): array => array_column($this->get("{$this->url}{$query}")->assertOk()->viewData('page')['props']['orders'], 'number');

    expect($numbers('?sort=total&direction=asc'))->toBe([$second->number, $third->number, $first->number])
        ->and($numbers('?sort=total&direction=desc'))->toBe([$first->number, $third->number, $second->number])
        ->and($numbers(''))->toBe([$third->number, $second->number, $first->number]);
});
