<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Payment\Application\ReconciliationLedger;
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
    C::store(stock: 5);
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
