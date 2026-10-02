<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Ordering\Contracts\Data\SalesDimension;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Reports\ReportsServiceProvider;

/**
 * Đơn chèn thẳng (không qua checkout) để kiểm soát thời điểm/giá trị. `$placedAt` theo UTC.
 *
 * @param  list<array{0: string, 1: ?string, 2: int, 3: int}>  $lines  [tên sản phẩm, thương hiệu, SL, thành tiền]
 */
function reportOrder(string $placedAt, int $total, array $lines = [], string $status = 'confirmed', string $payment = 'cod', string $source = 'web', ?int $customerId = null): int
{
    static $n = 0;
    $n++;
    $id = DB::table('orders')->insertGetId([
        'public_id' => strtoupper(Str::ulid()), 'number' => 'RPT'.$n, 'source' => $source, 'customer_id' => $customerId, 'currency_code' => 'VND',
        'order_status' => $status, 'payment_status' => 'pending', 'fulfillment_status' => 'unfulfilled', 'payment_method' => $payment,
        'subtotal_amount' => $total, 'discount_amount' => 10, 'shipping_amount' => 20, 'tax_amount' => 0, 'total_amount' => $total,
        'customer_snapshot' => '{}', 'shipping_address' => '{}', 'shipping_method' => '{}', 'reservation_key' => 'rk'.$n,
        'placed_at' => $placedAt, 'created_at' => $placedAt, 'updated_at' => $placedAt,
    ]);
    foreach ($lines as [$name, $brand, $quantity, $amount]) {
        DB::table('order_lines')->insert([
            'order_id' => $id, 'variant_id' => 1, 'sku' => 'SKU-'.$name, 'product_name' => $name, 'brand_name' => $brand, 'size_code' => 'M',
            'quantity' => $quantity, 'unit_amount' => $amount, 'subtotal_amount' => $amount, 'discount_amount' => 0, 'total_amount' => $amount,
            'tax_rate_bp' => 0, 'tax_amount' => 0, 'created_at' => $placedAt, 'updated_at' => $placedAt,
        ]);
    }

    return $id;
}

function installReports(): void
{
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(ReportsServiceProvider::ID);
        app(PluginManager::class)->enable(ReportsServiceProvider::ID);
    });
    app()->register(ReportsServiceProvider::class);
}

beforeEach(function () {
    // 02/10/2026 10:00 giờ VN
    Carbon::setTestNow('2026-10-02T03:00:00Z');
    // 01/10 23:30 VN (16:30Z) và 02/10 00:30 VN (01/10 17:30Z) — khác ngày theo giờ VN dù cùng ngày UTC.
    reportOrder('2026-10-01 16:30:00', 100_000, [['Áo thun', 'Lumière', 2, 100_000]], customerId: 1);
    reportOrder('2026-10-01 17:30:00', 300_000, [['Áo thun', 'Lumière', 1, 50_000], ['Quần jean', null, 1, 250_000]], status: 'pending', payment: 'bank_transfer', source: 'zalo', customerId: 1);
    reportOrder('2026-10-02 02:00:00', 200_000, [['Quần jean', null, 1, 200_000]], status: 'processing', customerId: 2);
    reportOrder('2026-10-02 02:30:00', 999_000, [['Áo thun', 'Lumière', 9, 999_000]], status: 'cancelled');
    reportOrder('2026-08-01 02:00:00', 50_000, [['Cũ', null, 1, 50_000]], status: 'completed');
});

afterEach(fn () => Carbon::setTestNow());

it('OrderStatistics: tổng, theo ngày giờ VN, theo chiều; bỏ đơn huỷ', function () {
    $stats = app(OrderStatistics::class);
    $from = new DateTimeImmutable('2026-10-01T00:00:00+07:00');
    $to = new DateTimeImmutable('2026-10-03T00:00:00+07:00');

    $totals = $stats->totals($from, $to);
    expect([$totals->ordersCount, $totals->revenue, $totals->cancelledCount, $totals->customersCount, $totals->discount, $totals->shipping, $totals->averageOrderValue()])
        ->toBe([3, 600_000, 1, 2, 30, 60, 200_000]);

    $daily = $stats->daily($from, $to, 'Asia/Ho_Chi_Minh');
    expect(array_map(fn ($d) => [$d->key, $d->label, $d->ordersCount, $d->revenue], $daily))->toBe([
        ['2026-10-01', '01/10', 1, 100_000],
        ['2026-10-02', '02/10', 2, 500_000],
    ]);

    expect(array_map(fn ($b) => [$b->key, $b->ordersCount, $b->quantity, $b->revenue], $stats->breakdown(SalesDimension::Product, $from, $to)))->toBe([
        ['Quần jean', 2, 2, 450_000],
        ['Áo thun', 2, 3, 150_000],
    ])->and(array_map(fn ($b) => [$b->key, $b->label, $b->revenue], $stats->breakdown(SalesDimension::Brand, $from, $to)))->toBe([
        ['', '', 450_000],
        ['Lumière', 'Lumière', 150_000],
    ])->and(array_map(fn ($b) => [$b->key, $b->ordersCount, $b->revenue], $stats->breakdown(SalesDimension::PaymentMethod, $from, $to)))->toBe([
        ['bank_transfer', 1, 300_000],
        ['cod', 2, 300_000],
    ])->and($stats->countByStatus())->toBe(['pending' => 1, 'confirmed' => 1, 'processing' => 1, 'completed' => 1, 'cancelled' => 1])
        ->and(fn () => $stats->totals($to, $from))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $stats->daily($from->modify('-400 days'), $to, 'UTC'))->toThrow(InvalidArgumentException::class);
});

it('widget Tổng quan + báo cáo của plugin; nhân viên thiếu quyền chỉ thấy widget đơn cần xử lý', function () {
    installReports();
    $manager = StaffUser::factory()->withPermissions(['admin.access', 'orders.view', 'reports.view'])->create();

    $this->actingAs($manager, 'staff')->get('/admin')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('widgets', fn ($widgets) => collect($widgets)->pluck('key')->all() === ['reports_revenue_today', 'reports_orders_to_process', 'reports_revenue_30d', 'reports_sales_14d', 'reports_top_products'])
            ->where('widgets.0.data.value', 500_000)
            ->where('widgets.0.data.hint', '2 đơn')
            ->where('widgets.0.plugin', 'vani.reports')
            ->where('widgets.1.data.value', 3)
            ->where('widgets.3.data.labels', fn ($labels) => count($labels) === 14 && $labels[13] === '02/10')
            ->where('widgets.4.data.rows.0', ['product' => 'Quần jean', 'quantity' => 2, 'revenue' => 450_000])
            ->where('navigation', fn ($items) => collect($items)->pluck('key')->contains('reports')));

    $this->actingAs($manager, 'staff')->get('/admin/reports')
        ->assertInertia(fn (Assert $page) => $page->has('reports', 5));

    $this->actingAs($manager, 'staff')->get('/admin/reports/sales-by-day?preset=7d')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('result.summary.0.value', 600_000)
            ->where('result.summary.1.hint', 'Đã huỷ 1')
            ->has('result.table.rows', 7)
            ->where('result.table.rows.6', ['date' => '2026-10-02', 'orders' => 2, 'revenue' => 500_000, 'average' => 250_000]));

    $this->actingAs($manager, 'staff')->get('/admin/reports/sales-by-channel?preset=7d')
        ->assertInertia(fn (Assert $page) => $page
            ->where('result.table.rows.0', ['group' => 'Website', 'orders' => 2, 'revenue' => 300_000, 'share' => 50])
            ->where('result.table.rows.1.group', 'Zalo'));

    $this->actingAs($manager, 'staff')->get('/admin/reports/sales-by-brand?preset=7d')
        ->assertInertia(fn (Assert $page) => $page->where('result.table.rows.0.group', '(Không có)')->where('result.table.rows.0.quantity', 2));

    expect($this->actingAs($manager, 'staff')->get('/admin/reports/sales-by-product/export?preset=7d')->streamedContent())
        ->toContain("\"Sản phẩm\",\"Số đơn\",\"Số lượng\",\"Doanh thu\",\"Tỷ trọng\"\n\"Quần jean\",2,2,450000,75");

    $clerk = StaffUser::factory()->withPermissions(['admin.access', 'orders.view'])->create();
    $this->actingAs($clerk, 'staff')->get('/admin')
        ->assertInertia(fn (Assert $page) => $page
            ->where('widgets', fn ($widgets) => collect($widgets)->pluck('key')->all() === ['reports_orders_to_process'])
            ->where('navigation', fn ($items) => ! collect($items)->pluck('key')->contains('reports')));
    $this->actingAs($clerk, 'staff')->get('/admin/reports/sales-by-day')->assertNotFound();
});
