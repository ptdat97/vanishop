<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\HelloWorld\HelloWorldServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| EP v3 X1 — màn vận hành Admin mở cho plugin: cột, bộ lọc, thao tác hàng loạt, công cụ tải file trên vận đơn, đổi trả,
| thanh toán, khuyến mãi, tồn kho, bảng giá; thao tác + tab trên trang đổi trả; bộ lọc phụ của SkuPicker. Tham chiếu:
| "Đánh dấu Hello" của vani.hello-world.
*/

beforeEach(function () {
    C::autoConfirmCod();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install('vani.hello-world');
        app(PluginManager::class)->enable('vani.hello-world');
    });
    app()->register(HelloWorldServiceProvider::class);
    ['s' => $this->s, 'm' => $this->m] = C::store(stock: 10);
    $this->place = function (): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    };
    $this->staff = fn (array $extra = []) => $this->actingAs(T::staff(['admin.access', 'hello-world.view', ...$extra]), 'staff');
    $this->flag = 'vani.hello-world:flag';
});

it('thanh toán: thao tác hàng loạt của plugin ghi vào bảng riêng; cột hiện dấu; bộ lọc chỉ còn mục đã đánh dấu', function () {
    ($this->place)();
    ($this->place)();
    [$first, $second] = Payment::query()->withoutGlobalScopes()->orderBy('id')->pluck('id')->all();
    ($this->staff)(['payments.view']);
    $props = fn (string $url = '/admin/payment/payments') => $this->get($url)->viewData('page')['props']['extensions'];

    $action = collect($props()['actions'])->firstWhere('key', $this->flag);
    $this->post($action['url'], ['ids' => [$first]])->assertSessionHas('success');

    expect($props()['values'][$first][$this->flag])->toBe('★ Đã đánh dấu')
        ->and($props()['values'][$second][$this->flag])->toBeNull()
        ->and(collect($props()['tools'])->pluck('key')->all())->toBe(['vani.hello-world:import_flags']);
    $this->get('/admin/payment/payments?ext[vani.hello-world:flagged]=yes')->assertInertia(fn (Assert $page) => $page->has('payments', 1)->where('payments.0.id', $first));
});

it('công cụ tải file trên vận đơn: kiểm quyền, loại file; plugin đọc file + ô phụ, trả thông báo', function () {
    ($this->place)();
    $shipment = Shipment::query()->withoutGlobalScopes()->sole();
    $this->actingAs(T::staff(['admin.access', 'fulfillment.view']), 'staff');
    $url = route('admin.extensions.tool', ['resource' => 'shipment', 'plugin' => 'vani.hello-world', 'key' => 'import_flags']);
    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('ids.csv', "{$shipment->id}\n")])->assertForbidden();
    ($this->staff)(['fulfillment.view']);

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('ids.txt', '1')])->assertSessionHasErrors('file');
    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('ids.csv', "x\n")])->assertSessionHasErrors('file');
    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('ids.csv', "{$shipment->id}\n"), 'note' => 'đợt 1'])->assertSessionHas('success', 'Đã đánh dấu 1 mục (đợt 1).');

    $flagged = $this->get('/admin/fulfillment/shipments?ext[vani.hello-world:flagged]=yes')->viewData('page')['props'];
    expect($flagged['shipments'])->toHaveCount(1)->and($flagged['extensions']['values'][$shipment->id][$this->flag])->toBe('★ Đã đánh dấu');
    $this->get('/admin/fulfillment/shipments')->assertInertia(fn (Assert $page) => $page->where('extensions.tools.0.key', 'vani.hello-world:import_flags'));
});

it('đổi trả: thao tác hàng loạt của plugin nối sau của Core; trang chi tiết có thao tác + tab của plugin', function () {
    $order = ($this->place)();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($order) {
        $shipment = Shipment::query()->where('order_id', $order->id)->sole();
        app(FulfillmentService::class)->book($shipment->id, 'T1');
        foreach ([ShipmentStatus::PickedUp, ShipmentStatus::Delivered] as $status) {
            app(FulfillmentService::class)->updateStatus($shipment->id, $status, "t:{$status->value}", 'staff');
        }
    });
    ($this->staff)(['orders.view', 'returns.view', 'returns.manage']);
    $this->post('/admin/returns/returns', ['order_id' => $order->id, 'lines' => [$order->lines()->value('id') => 1], 'reason_code' => 'wrong_size'])->assertRedirect();
    $return = ReturnRequest::query()->sole();

    $this->get('/admin/returns/returns')->assertInertia(fn (Assert $page) => $page->where('extensions.actions.0.key', $this->flag)->where('actions.0.key', 'approve'));
    $detail = $this->get("/admin/returns/returns/{$return->id}")->viewData('page')['props']['extensions'];
    expect(array_column($detail['actions'], 'key'))->toBe(['vani.hello-world:flag_one'])->and($detail['tabs'][0]['rows'][0]['value'])->toBe('Không');

    $this->post($detail['actions'][0]['url'], ['ids' => [$return->id]])->assertSessionHas('success', 'Đã đánh dấu Hello.');
    expect($this->get("/admin/returns/returns/{$return->id}")->viewData('page')['props']['extensions']['tabs'][0]['rows'][0]['value'])->toBe('Có');
});

it('tồn kho, bảng giá, SkuPicker dùng chung id SKU: lọc, cột theo SKU; SkuPicker lọc theo plugin cả khi chưa gõ', function () {
    DB::table('plg_hello_world_flags')->insert(['subject' => 'variant', 'subject_id' => $this->m->id, 'created_at' => now()]);
    $sale = P::priceList(['code' => 'sale-1', 'type' => 'sale', 'priority' => 10], [$this->s->id => [200_000], $this->m->id => [200_000]]);
    ($this->staff)(['inventory.view', 'inventory.adjust', 'pricing.view', 'pricing.manage']);

    $stock = $this->get('/admin/inventory/stock?ext[vani.hello-world:flagged]=yes')->viewData('page')['props'];
    expect(collect($stock['products'])->flatMap(fn ($product) => array_column($product['variants'], 'id'))->all())->toBe([$this->m->id])
        ->and($stock['extensions']['values'][$this->m->id][$this->flag])->toBe('★ Đã đánh dấu');

    $this->get("/admin/pricing/price-lists/{$sale->id}/prices?ext[vani.hello-world:flagged]=yes")->assertInertia(fn (Assert $page) => $page
        ->has('rows', 1)->where('rows.0.variant_id', $this->m->id)->where('skuPickerFilters.0.key', 'vani.hello-world:flagged'));
    $this->getJson("/admin/pricing/price-lists/{$sale->id}/candidates?ext[vani.hello-world:flagged]=yes")->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonCount(1, 'data.0.variants')->assertJsonPath('data.0.variants.0.id', $this->m->id);
});

it('khuyến mãi: cột + thao tác hàng loạt của plugin; plugin tắt thì không còn phần mở rộng', function () {
    $promotion = C::promotion([], []);
    ($this->staff)(['promotion.view', 'promotion.manage']);
    $action = collect($this->get('/admin/promotion/promotions')->viewData('page')['props']['extensions']['actions'])->firstWhere('key', $this->flag);
    $this->post($action['url'], ['ids' => [$promotion->id]])->assertSessionHas('success');

    expect($this->get('/admin/promotion/promotions')->viewData('page')['props']['extensions']['values'][$promotion->id][$this->flag])->toBe('★ Đã đánh dấu');
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PluginManager::class)->disable('vani.hello-world'));
    $this->get('/admin/promotion/promotions')->assertInertia(fn (Assert $page) => $page->where('extensions.columns', [])->where('extensions.actions', [])->where('extensions.tools', []));
});

it('mẫu phiếu giao của plugin (EP v3 X3, PackingSlipTemplate): chọn trên trang in; mẫu không có → mẫu chuẩn', function () {
    $order = ($this->place)();
    ($this->staff)(['orders.view']);

    $this->get("/admin/orders/orders/print?ids[]={$order->id}")->assertOk()->assertSee('A6 máy in nhiệt (Hello)')->assertDontSee('data-slip-a6', false);
    $this->get("/admin/orders/orders/print?ids[]={$order->id}&template=hello-a6")->assertOk()->assertSee("data-slip-a6=\"{$order->number}\"", false)->assertSee('Cho khách xem hàng');
    $this->get("/admin/orders/orders/print?ids[]={$order->id}&template=khong-co")->assertOk()->assertDontSee('data-slip-a6', false);
});
