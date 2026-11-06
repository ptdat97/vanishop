<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Contracts\OrderStatusAction;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Tests\Fixtures\RequeryPaymentStatusAction;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Thẻ "Trạng thái đơn" (0.3.98): 4 chiều trạng thái + thao tác của Core (xác nhận, hoàn tất, huỷ) và của plugin qua extension
| point OrderStatusAction (Core hiển thị, kiểm quyền, validate, gọi perform, ghi audit).
*/

beforeEach(function () {
    config(['vani.bank-transfer.account' => ['bank' => 'VCB', 'account_number' => '0123456789', 'account_name' => 'VANI']]);
    ['s' => $this->s] = C::store(stock: 20);
    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm_mode', 'off'));
    $this->place = function (string $method = 'cod'): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->staff = fn (array $permissions = ['orders.manage', 'orders.cancel', 'payments.confirm']) => $this->actingAs(T::staff(['admin.access', 'orders.view', ...$permissions]), 'staff');
    $this->card = fn (Order $order): array => $this->get("/admin/orders/orders/{$order->id}")->assertOk()->viewData('page')['props']['statusCard'];
    $this->action = fn (Order $order, string $key): ?array => collect(($this->card)($order)['actions'])->firstWhere('key', $key);
    RequeryPaymentStatusAction::$mode = 'ok';
    RequeryPaymentStatusAction::$performed = [];
});

it('Core: 4 chiều trạng thái; Xác nhận (bước tiếp theo) và Huỷ khi chờ xác nhận; Hoàn tất chỉ khi đã giao hết, không có đổi/trả đang mở', function () {
    $order = ($this->place)();
    ($this->staff)();

    $card = ($this->card)($order);
    expect(collect($card['rows'])->map(fn (array $row): string => "{$row['label']}: {$row['text']}")->all())->toBe([
        'Đơn hàng: Chờ xác nhận', 'Thanh toán: COD chờ thu', 'Giao hàng: Chưa giao', 'Đổi/trả: Không',
    ])->and(collect($card['actions'])->pluck('key')->all())->toBe(['confirm', 'cancel'])
        ->and($card['actions'][0]['primary'])->toBeTrue();

    $this->post($card['actions'][0]['url'])->assertRedirect();
    expect($order->fresh()->order_status->value)->toBe('processing');

    // Chưa giao: không hoàn tất được (nút không hiện, gọi thẳng bị từ chối).
    expect(($this->action)($order, 'complete'))->toBeNull();
    $this->post("/admin/orders/orders/{$order->id}/complete", ['reason' => 'x'])->assertSessionHasErrors('business');

    DB::table('orders')->where('id', $order->id)->update(['fulfillment_status' => 'delivered', 'return_status' => 'requested']);
    expect(($this->action)($order, 'complete'))->toBeNull();

    DB::table('orders')->where('id', $order->id)->update(['return_status' => 'none']);
    $complete = ($this->action)($order, 'complete');
    $this->post($complete['url'], ['reason' => ''])->assertSessionHasErrors('reason');
    $this->post($complete['url'], ['reason' => 'Khách xác nhận qua Zalo'])->assertRedirect()->assertSessionHas('success', 'Đã hoàn tất đơn.');
    expect($order->fresh()->order_status->value)->toBe('completed')
        ->and(DB::table('audit_logs')->where('action', 'order.completed')->where('subject_id', (string) $order->id)->exists())->toBeTrue()
        ->and(($this->card)($order)['actions'])->toBe([]);
});

it('plugin cắm thao tác đổi trạng thái (OrderStatusAction): hiện khi áp dụng + đủ quyền; validate ô nhập; perform qua contract Core; audit', function () {
    app(Extensions::class)->tag([RequeryPaymentStatusAction::class], OrderStatusAction::TAG);
    $transfer = ($this->place)('manual_bank_transfer');
    $cod = ($this->place)();
    ($this->staff)();

    $action = ($this->action)($transfer, 'ext-test.requery');
    expect($action)->toMatchArray(['label' => 'Đối soát với cổng', 'primary' => true])
        ->and($action['fields'][0])->toMatchArray(['key' => 'reference', 'required' => true])
        // Không áp dụng cho đơn COD.
        ->and(($this->action)($cod, 'ext-test.requery'))->toBeNull();

    $this->post($action['url'], [])->assertSessionHasErrors('reference');
    $this->post($action['url'], ['reference' => 'FT001'])->assertRedirect()->assertSessionHas('success', 'Cổng xác nhận đã thanh toán.');
    expect($transfer->fresh()->payment_status)->toBe('paid')
        ->and(RequeryPaymentStatusAction::$performed)->toBe([['reference' => 'FT001']])
        ->and(DB::table('audit_logs')->where('action', 'order.status_action')->where('subject_id', (string) $transfer->id)->exists())->toBeTrue()
        // Đã thanh toán: không còn áp dụng → gọi lại bị từ chối.
        ->and(($this->action)($transfer, 'ext-test.requery'))->toBeNull();
    $this->post($action['url'], ['reference' => 'FT002'])->assertSessionHasErrors('business');
    $this->post("/admin/orders/orders/{$transfer->id}/status-actions/khong-co", [])->assertNotFound();
});

it('plugin: không đủ quyền thì không thấy/không gọi được; lỗi nghiệp vụ hiện cho nhân viên, lỗi khác báo chung', function () {
    app(Extensions::class)->tag([RequeryPaymentStatusAction::class], OrderStatusAction::TAG);
    $transfer = ($this->place)('manual_bank_transfer');
    $url = "/admin/orders/orders/{$transfer->id}/status-actions/test.requery";

    ($this->staff)(['orders.manage']);
    expect(($this->action)($transfer, 'ext-test.requery'))->toBeNull();
    $this->post($url, ['reference' => 'FT001'])->assertForbidden();

    ($this->staff)();
    RequeryPaymentStatusAction::$mode = 'business';
    $this->post($url, ['reference' => 'FT001'])->assertSessionHasErrors(['business' => 'Chỉ hoàn tất được đơn đã giao hết và không có yêu cầu đổi/trả đang mở.']);
    RequeryPaymentStatusAction::$mode = 'crash';
    $this->post($url, ['reference' => 'FT001'])->assertSessionHasErrors(['business' => 'Thao tác của plugin bị lỗi. Thử lại hoặc báo quản trị.']);
    expect($transfer->fresh()->payment_status)->toBe('unpaid');
});
