<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Application\ReconciliationLedger;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\HelloWorld\HelloWorldServiceProvider;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| EP v3 X1 trên trình duyệt thật: sáu màn vận hành mở cho plugin (vani.hello-world bật) — trang mở được, bộ lọc / công cụ
| tải file của plugin hiện, không lỗi JS.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install('vani.hello-world');
        app(PluginManager::class)->enable('vani.hello-world');
    });
    app()->register(HelloWorldServiceProvider::class);
    ['s' => $this->s, 'm' => $this->m] = C::store(stock: 5);
    $this->actingAs(T::staff(['admin.access', 'hello-world.view', 'fulfillment.view', 'returns.view', 'payments.view', 'promotion.view', 'inventory.view', 'inventory.adjust']), 'staff');
});

it('sáu màn vận hành hiện bộ lọc và công cụ của plugin, không lỗi JS', function (string $url) {
    visit($url)->assertSee('Nhập đánh dấu Hello')->click('Nhập đánh dấu Hello')->assertSee('mỗi dòng một id ở cột đầu')->assertNoJavaScriptErrors();
})->with([
    'vận đơn' => '/admin/fulfillment/shipments',
    'đổi trả' => '/admin/returns/returns',
    'thanh toán' => '/admin/payment/payments',
    'khuyến mãi' => '/admin/promotion/promotions',
    'tồn kho' => '/admin/inventory/stock',
]);

it('chọn hai dòng đối soát rồi đánh dấu cả hai bằng một ghi chú', function () {
    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.confirm']), 'staff');
    $ledger = app(ReconciliationLedger::class);
    $ledger->record('test-bank', 'TX-1', false);
    $ledger->record('test-bank', 'TX-2', false);

    visit('/admin/payment/payments')
        ->click('[data-reconciliation] input[aria-label="TX-1"]')
        ->click('[data-reconciliation] input[aria-label="TX-2"]')
        ->click('[data-resolve-mismatches]')
        ->fill('[role="alertdialog"] input', 'Đã kiểm tra sao kê')
        ->click('[role="alertdialog"] button[type="submit"]')
        ->assertSee('Đã xử lý 2 dòng đối soát')
        ->assertMissing('[data-reconciliation]')
        ->assertNoJavaScriptErrors();
});

it('chọn tình trạng từng dòng trong hộp thoại nhận hàng trả', function () {
    C::autoConfirmCod();
    $this->actingAs(T::staff(['admin.access', 'returns.view', 'returns.manage']), 'staff');
    $cart = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $id = $cart->json('data.id');
    $headers = ['X-Vani-Cart-Token' => $cart->json('meta.token')];
    foreach ([$this->s, $this->m] as $variant) {
        $this->postJson("/api/storefront/v1/carts/{$id}/lines", ['variant_id' => $variant->id, 'quantity' => 1], $headers)->assertOk();
    }
    $this->postJson("/api/storefront/v1/checkout/{$id}/orders", C::orderPayload(['expected_total' => 500_000]), [...$headers, 'Idempotency-Key' => "k-{$id}"])->assertCreated();
    $order = Order::query()->sole();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($order): void {
        $shipment = Shipment::query()->where('order_id', $order->id)->sole();
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'RT-123');
        $service->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'RT-picked', 'staff');
        $service->updateStatus($shipment->id, ShipmentStatus::Delivered, 'RT-delivered', 'staff');
    });
    $this->post('/admin/returns/returns', ['order_id' => $order->id, 'lines' => array_fill_keys($order->lines()->pluck('id')->all(), 1), 'reason_code' => 'wrong_size'])->assertRedirect();
    $return = ReturnRequest::query()->sole();
    $this->post('/admin/returns/returns/bulk-approve', ['ids' => [$return->id]])->assertSessionHasNoErrors();
    $sellable = $return->lines()->where('variant_id', $this->s->id)->sole();
    $damaged = $return->lines()->where('variant_id', $this->m->id)->sole();

    visit('/admin/returns/returns')
        ->click('input[aria-label="Chọn '.$return->number.'"]')
        ->click('[data-extension-action="receive_mixed"]')
        ->assertSee($this->m->sku)
        ->assertVisible('[data-mixed-return-receive] button[type="submit"]:disabled')
        ->click("#return-condition-{$sellable->id}")->click('[role="option"] >> text=Bán lại được')
        ->assertVisible('[data-mixed-return-receive] button[type="submit"]:disabled')
        ->click("#return-condition-{$damaged->id}")->click('[role="option"] >> text=Hư hỏng')
        ->click('[data-mixed-return-receive] button[type="submit"]')
        ->assertSee('Đã nhận hàng 1 yêu cầu theo tình trạng từng dòng')
        ->assertNoJavaScriptErrors();

    expect($return->fresh()->status)->toBe(ReturnStatus::Received)
        ->and($sellable->fresh()->condition)->toBe('sellable')
        ->and($damaged->fresh()->condition)->toBe('damaged');
});
