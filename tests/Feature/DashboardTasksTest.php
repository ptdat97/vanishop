<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Inventory\Application\Listeners\StockDashboardTasks;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| "Việc cần làm" đầu trang tổng quan Admin (0.3.101, SME): mỗi module góp ô hàng đợi có số đếm qua slot
| vani.admin.dashboard.tasks; bấm vào → danh sách đã lọc; chỉ ô thuộc quyền nhân viên.
*/

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m] = C::store(stock: 2);
    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm_mode', 'off'));
    Cache::forget(StockDashboardTasks::CACHE_KEY);
    $this->place = function (): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->tasks = fn (): array => collect($this->get('/admin')->assertOk()->viewData('page')['props']['tasks'])->keyBy('key')->all();
});

it('đủ quyền: ô của mọi module theo thứ tự, số đếm đúng, link tới danh sách đã lọc', function () {
    ($this->place)();
    $old = ($this->place)();
    DB::table('orders')->where('id', $old->id)->update(['placed_at' => now()->subHours(30)]);
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'returns.view', 'fulfillment.view', 'payments.view', 'inventory.view']), 'staff');

    $tasks = ($this->tasks)();
    expect(array_keys($tasks))->toBe(['orders.to_confirm', 'orders.aged', 'orders.awaiting_payment', 'orders.to_ship', 'returns.requested', 'returns.received', 'orders.issue', 'shipments.failed_attempt', 'payments.refunds_requested', 'stock.out', 'stock.low'])
        ->and($tasks['orders.to_confirm']['count'])->toBe(2)
        ->and($tasks['orders.aged']['count'])->toBe(1)
        ->and($tasks['orders.aged']['url'])->toBe(route('admin.orders.orders.index', ['aged' => 24]))
        ->and($tasks['returns.requested']['count'])->toBe(0)
        // Chỉ hiện "Đặt vận đơn lỗi" khi có.
        ->and($tasks)->not->toHaveKey('shipments.booking_failed')
        // Kho 2 → giữ 2 cho 2 đơn: size S hết hàng bán được; size M còn 2 (≤ ngưỡng 3) → sắp hết.
        ->and($tasks['stock.out']['count'])->toBe(1)
        ->and($tasks['stock.low']['count'])->toBe(1)
        ->and($tasks['stock.low']['url'])->toBe(route('admin.inventory.stock.index', ['filter' => 'low']));

    $this->get($tasks['orders.aged']['url'])->assertOk()->assertInertia(fn ($page) => $page->has('orders', 1)->where('orders.0.id', $old->id)->where('filters.aged', '24'));
});

it('chỉ ô thuộc quyền; không có ô nào thì trang vẫn mở', function () {
    ($this->place)();
    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    expect(collect(($this->tasks)())->keys()->every(fn (string $key): bool => str_starts_with($key, 'orders.')))->toBeTrue();

    $this->actingAs(T::staff(['admin.access']), 'staff');
    expect(($this->tasks)())->toBe([]);
});
