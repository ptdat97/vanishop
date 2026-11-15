<?php

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Preorder\PreorderServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| vani.preorder — tham chiếu EP v3 X7 (ADR-047): IncomingStock (nhập từ file ở Tồn kho, cột "Đang về") + BackorderPolicy
| (bán vượt tồn tới số đang về), trang sản phẩm "Đặt trước", chặn đẩy hãng đơn còn chờ hàng, nhập kho trừ số đang về.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(PreorderServiceProvider::ID);
        app(PluginManager::class)->enable(PreorderServiceProvider::ID);
    });
    app()->register(PreorderServiceProvider::class);
    ['s' => $this->s, 'm' => $this->m, 'location' => $this->location] = C::store(stock: 0);
    $this->style = $this->s->style;
    $this->place = function (): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'order-key-'.$created->json('data.id')])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    };
    $this->ats = fn () => app(AvailabilityReader::class)->forVariants([$this->s->id])[$this->s->id];
});

it('nhập hàng đang về từ file ở Tồn kho: kiểm quyền, SKU lạ, ngày sai; cột "Đang về" hiện số và ngày', function () {
    $url = route('admin.extensions.tool', ['resource' => 'stock', 'plugin' => PreorderServiceProvider::ID, 'key' => 'import_incoming']);
    $file = fn (string $content) => UploadedFile::fake()->createWithContent('incoming.csv', $content);

    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');
    $this->post($url, ['file' => $file("{$this->s->sku},5\n")])->assertForbidden();

    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $this->post($url, ['file' => $file("{$this->s->sku},5,31-11\n")])->assertSessionHasErrors('file');
    $this->post($url, ['file' => $file("SKU,so_luong,ngay_ve\n{$this->s->sku},5,30/11/2026\nKHONG-CO,1,\n")])
        ->assertSessionHas('success', 'Đã cập nhật hàng đang về cho 1 SKU. Không tìm thấy: KHONG-CO.');

    expect(app(IncomingStock::class)->forVariants([$this->s->id])[$this->s->id])->quantity->toBe(5)->expectedOn->toBe('2026-11-30')
        ->and($this->get('/admin/inventory/stock')->viewData('page')['props']['extensions']['values'][$this->s->id]['vani.preorder:incoming'])->toBe('5 · về 30/11');
});

it('SKU hết hàng có hàng đang về: bán được tới % đang về; trang sản phẩm báo "Đặt trước"; tắt % thì hết', function () {
    expect(($this->ats)())->toBe(0);
    T::seed(fn () => app(IncomingStock::class)->set($this->s->id, 10, '2026-11-30', 'staff'));
    T::seed(fn () => app(Settings::class)->set(PreorderServiceProvider::ID, 'share_percent', 50));
    expect(($this->ats)())->toBe(5);

    $extension = $this->getJson('/api/storefront/v1/products/'.$this->style->slug)->assertOk()->json('data.extensions')[PreorderServiceProvider::ID] ?? null;
    expect($extension)->toBe(['variants' => [(string) $this->s->id => '2026-11-30']])
        ->and($this->get('/san-pham/'.$this->style->slug)->assertOk()->getContent())->toContain('data-preorder')->toContain('Đặt trước — dự kiến có hàng __D__');

    T::seed(fn () => app(Settings::class)->set(PreorderServiceProvider::ID, 'share_percent', 0));
    expect(($this->ats)())->toBe(0);
});

it('đơn đặt trước: thẻ "chờ hàng" trên trang đơn, không đẩy lên hãng được; nhập kho trừ số đang về và mở chốt', function () {
    C::autoConfirmCod();
    T::seed(fn () => app(IncomingStock::class)->set($this->s->id, 3, '2026-11-30', 'staff'));
    $order = ($this->place)();
    $shipment = Shipment::query()->where('order_id', $order->id)->sole();

    expect(app(FulfillmentService::class)->pushBlockers($shipment))->toBe(["Đơn đặt trước còn chờ hàng: {$this->s->sku} × 1 — nhập kho khi hàng về rồi đẩy lên hãng"]);
    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    $panels = collect($this->get("/admin/orders/orders/{$order->id}")->viewData('page')['props']['panels'])->keyBy('title');
    expect($panels['Đặt trước — chờ hàng']['rows'])->toBe([['label' => "{$this->s->sku} × 1", 'value' => 'dự kiến 30/11']]);

    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StockAdjustmentService::class)->adjust($this->location, $this->s->id, 3, 'receive'));

    expect(app(IncomingStock::class)->forVariants([$this->s->id]))->toBe([])
        ->and(app(FulfillmentService::class)->pushBlockers($shipment->fresh()))->toBe([])
        ->and(($this->ats)())->toBe(2);
});
