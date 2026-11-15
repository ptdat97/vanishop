<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\CustomerNotes;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\CodRiskGuard\CodRiskGuardServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| vani.cod-risk-guard — tham chiếu EP v3 X2: OrderRiskSignal (tín hiệu trên "Cần xác nhận") + hook vani.order.before_confirm
| (chặn xác nhận ở mọi đường: nhân viên, hàng loạt, tự xác nhận của vani.cod).
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CodRiskGuardServiceProvider::ID);
        app(PluginManager::class)->enable(CodRiskGuardServiceProvider::ID);
    });
    app()->register(CodRiskGuardServiceProvider::class);
    ['s' => $this->s] = C::store(stock: 20);
    $this->place = function (string $phone = '0912345678'): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000, 'contact' => ['phone' => $phone]]), [...$headers, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    };
    $this->blacklist = fn (string $phones) => T::seed(fn () => app(Settings::class)->set(CodRiskGuardServiceProvider::ID, 'blacklist', $phones));
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage']), 'staff');
});

it('SĐT trong danh sách đen: tín hiệu cao trên "Cần xác nhận" và trang đơn; chặn xác nhận từng đơn và hàng loạt; gỡ khỏi danh sách thì xác nhận được', function () {
    ($this->blacklist)("+84 912 345 678\n0987000000");
    $order = ($this->place)();
    $clean = ($this->place)('0933000111');

    $rows = collect($this->get('/admin/orders/orders?stage=to_confirm')->viewData('page')['props']['orders'])->keyBy('id');
    expect($rows[$order->id]['risks'][0])->toMatchArray(['level' => 'high', 'label' => 'SĐT trong danh sách đen', 'source' => CodRiskGuardServiceProvider::ID])
        ->and($rows[$clean->id]['risks'])->toBe([])
        ->and($this->get("/admin/orders/orders/{$order->id}")->viewData('page')['props']['risks'][0]['level'])->toBe('high');

    $this->post("/admin/orders/orders/{$order->id}/confirm")->assertSessionHasErrors(['business' => 'Chưa xác nhận được đơn: SĐT trong danh sách đen của cửa hàng — gọi xác nhận với khách rồi gỡ số khỏi danh sách đen (Cấu hình → Chống bom hàng COD)']);
    $this->post('/admin/orders/orders/bulk-confirm', ['ids' => [$order->id, $clean->id]])->assertSessionHas('success')->assertSessionHasErrors('business');
    expect($order->fresh()->order_status->value)->toBe('pending')->and($clean->fresh()->order_status->value)->not->toBe('pending');

    ($this->blacklist)('');
    $this->post("/admin/orders/orders/{$order->id}/confirm")->assertSessionHasNoErrors();
    expect($order->fresh()->order_status->value)->not->toBe('pending');
});

it('tự xác nhận COD (điểm mở rộng của vani.cod) cũng bị chặn: đơn rủi ro cao chờ nhân viên', function () {
    C::autoConfirmCod();
    ($this->blacklist)('0912345678');

    $risky = ($this->place)();
    $normal = ($this->place)('0933000111');

    expect($risky->order_status->value)->toBe('pending')->and($normal->order_status->value)->toBe('processing');
});

it('lịch sử hoàn về theo SĐT: dưới ngưỡng là trung bình, từ ngưỡng là cao', function () {
    $first = ($this->place)();
    $order = ($this->place)();
    DB::table('orders')->where('id', $first->id)->update(['fulfillment_status' => 'returned_to_sender', 'order_status' => 'cancelled']);

    $risk = fn () => $this->get("/admin/orders/orders/{$order->id}")->viewData('page')['props']['risks'];
    expect($risk()[0])->toMatchArray(['level' => 'medium', 'label' => 'Hoàn về 1 lần']);

    T::seed(fn () => app(Settings::class)->set(CodRiskGuardServiceProvider::ID, 'returned_threshold', 1));
    expect($risk()[0])->toMatchArray(['level' => 'high', 'label' => 'Hoàn về 1 lần']);
});

it('đơn hoàn về: khách được gắn nhãn "bom-hang" và có ghi chú CSKH (EP v3 X6)', function () {
    C::autoConfirmCod();
    $order = ($this->place)();
    $shipment = Shipment::query()->where('order_id', $order->id)->sole();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($shipment) {
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'T1');
        foreach ([ShipmentStatus::PickedUp, ShipmentStatus::Returning, ShipmentStatus::Returned] as $status) {
            $service->updateStatus($shipment->id, $status, "t:{$status->value}", 'staff');
        }
    });

    expect(app(CustomerSegments::class)->segmentOf($order->customer_id)->tags)->toBe(['bom-hang'])
        ->and(app(CustomerNotes::class)->latest($order->customer_id)[0])->toMatchArray(['note' => "Đơn {$order->number} bị hoàn về (khách không nhận).", 'source' => 'system:vani.cod-risk-guard']);
});
