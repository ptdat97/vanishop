<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Ordering\Contracts\ConfirmationChannel;
use Modules\Ordering\Contracts\Data\RiskSignal;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Contracts\OrderActions;
use Modules\Ordering\Contracts\OrderRisks;
use Modules\Ordering\Contracts\OrderRiskSignal;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Tests\Fixtures\LogConfirmationChannel;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../Fixtures/ContractExtensions.php';

/*
| EP v3 X2 — lệnh nhân viên cho plugin (OrderActions), hook chặn xác nhận, gộp tín hiệu rủi ro, kênh xác nhận.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 20);
    $this->place = function (): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    };
    $this->event = fn (Order $order, string $type) => DB::table('order_events')->where('order_id', $order->id)->where('type', $type)->latest('id')->first();
});

it('OrderActions: plugin xác nhận, ghi chú, huỷ với nguồn system:<plugin> — cùng chính sách và lịch sử như nhân viên', function () {
    [$order, $other] = [($this->place)(), ($this->place)()];
    $actions = app(OrderActions::class);

    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($actions, $order, $other) {
        $actions->addNote($order->id, 'Khách bấm Đồng ý qua Zalo OA', 'system:vani.zalo-zns');
        $actions->confirm($order->id, 'customer_replied_yes', 'system:vani.zalo-zns');
        $actions->cancel($other->id, 'customer_replied_no', 'system:vani.zalo-zns');
    });

    expect($order->fresh()->order_status->value)->not->toBe('pending')
        ->and(DB::table('order_events')->where('order_id', $order->id)->where('to_status', 'confirmed')->value('source'))->toBe('system:vani.zalo-zns')
        ->and(($this->event)($order, 'note')->source)->toBe('system:vani.zalo-zns')
        ->and($other->fresh()->order_status->value)->toBe('cancelled');
    expect(fn () => $actions->cancel($order->id, 'x', 'system:vani.zalo-zns'))->not->toThrow(OrderActionRejected::class);
});

it('hook vani.order.before_confirm chặn mọi đường xác nhận; tín hiệu rủi ro của plugin lỗi bị bỏ, trang vẫn mở', function () {
    $order = ($this->place)();
    Hook::onValidate('vani.order.before_confirm', fn ($data): array => $data->id === $order->id ? ['Chưa gọi khách'] : []);
    app(Extensions::class)->tag([BrokenRiskSignal::class, FixedRiskSignal::class], OrderRiskSignal::TAG);

    expect(fn () => app(OrderActions::class)->confirm($order->id, 'x', 'system:test'))->toThrow(OrderActionRejected::class, 'Chưa xác nhận được đơn: Chưa gọi khách')
        ->and(app(OrderRisks::class)->forOrders([$order->id])[$order->id])->toHaveCount(2)
        ->and(app(OrderRisks::class)->forOrders([$order->id])[$order->id][0]->level)->toBe('high')
        ->and(app(OrderRisks::class)->hasHigh($order->id))->toBeTrue();
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage']), 'staff')->get("/admin/orders/orders/{$order->id}")->assertOk();
});

it('kênh xác nhận của plugin: nút trên đơn "Cần xác nhận" và hàng loạt; gửi ghi lịch sử đơn; đơn đã xác nhận không gửi', function () {
    app(Extensions::class)->tag([LogConfirmationChannel::class], ConfirmationChannel::TAG);
    [$order, $confirmed] = [($this->place)(), ($this->place)()];
    DB::table('orders')->where('id', $confirmed->id)->update(['order_status' => 'confirmed']);
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage']), 'staff');

    $action = collect($this->get("/admin/orders/orders/{$order->id}")->viewData('page')['props']['statusCard']['actions'])->firstWhere('key', 'confirmation-test-log');
    $this->post($action['url'])->assertSessionHas('success', "Đã ghi yêu cầu xác nhận đơn {$order->number}.");
    $this->post("/admin/orders/orders/{$confirmed->id}/confirmation/test-log")->assertSessionHasErrors('business');
    $bulk = collect($this->get('/admin/orders/orders')->viewData('page')['props']['extensions']['actions'])->firstWhere('key', 'confirmation_test-log');
    $this->post($bulk['url'], ['ids' => [$order->id, $confirmed->id]])->assertSessionHas('success', 'Đã gửi yêu cầu xác nhận qua Ghi log (thử) cho 1 đơn; bỏ qua 1 đơn.');

    expect(DB::table('order_events')->where('order_id', $order->id)->where('type', 'note')->count())->toBe(2)
        ->and(json_decode((string) ($this->event)($order, 'note')->data, true)['note'])->toStartWith('Gửi yêu cầu xác nhận qua Ghi log (thử)');
});

final class BrokenRiskSignal implements OrderRiskSignal
{
    public function assess(array $orderIds): array
    {
        throw new RuntimeException('plugin hỏng');
    }
}

final class FixedRiskSignal implements OrderRiskSignal
{
    public function assess(array $orderIds): array
    {
        return array_fill_keys($orderIds, [new RiskSignal('low', 'Khách mới'), new RiskSignal('high', 'Địa chỉ thiếu số nhà')]);
    }
}
