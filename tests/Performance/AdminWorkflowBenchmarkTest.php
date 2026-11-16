<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Pricing\Application\BulkBasePrice;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
| Đo thao tác Admin một ngày làm việc của shop 1.000 sản phẩm / 3.000 SKU (3 size mỗi sản phẩm) và ~120 đơn/ngày.
| Không chạy mặc định (nhóm `performance`). Chạy trên MySQL riêng — không đụng DB dev:
|
|   DB_CONNECTION=mysql DB_DATABASE=vanishop_bench php -d memory_limit=2G vendor/bin/pest --group=performance
|
| Kết quả: bảng theo thao tác (ms trung vị của 3 lần, số truy vấn, KB phản hồi, số lần/ngày, tổng ms/ngày) in ra màn hình
| và ghi `storage/logs/admin-benchmark.md`. Ngân sách ở cuối test chặn hồi quy: không thao tác nào vượt trần truy vấn.
*/

require_once __DIR__.'/../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

const BENCH_PRODUCTS = 1000;
const BENCH_ORDERS = 120;

/**
 * @return array{ms: float, queries: int, kb: float, status: int}
 */
function benchMeasure(Closure $request): array
{
    static $counter = null;
    if ($counter === null) {
        $counter = new ArrayObject(['queries' => 0, 'sql_ms' => 0.0, 'log' => []]);
        DB::listen(function ($query) use ($counter): void {
            $counter['queries']++;
            $counter['sql_ms'] += $query->time;
            if (getenv('BENCH_TRACE')) {
                $log = $counter['log'];
                $log[] = sprintf('%7.1f ms  %s', $query->time, mb_substr(preg_replace('/\s+/', ' ', $query->sql), 0, 300));
                $counter['log'] = $log;
            }
        });
    }

    $runs = [];
    for ($i = 0; $i < 3; $i++) {
        $counter['queries'] = 0;
        $counter['sql_ms'] = 0.0;
        $counter['log'] = [];
        $started = hrtime(true);
        /** @var TestResponse $response */
        $response = $request($i);
        $base = $response->baseResponse;
        // File xuất: tính cả thời gian sinh nội dung.
        $content = match (true) {
            $base instanceof StreamedResponse => (string) $response->streamedContent(),
            $base instanceof BinaryFileResponse => (string) file_get_contents($base->getFile()->getPathname()),
            default => (string) $base->getContent(),
        };
        $ms = (hrtime(true) - $started) / 1e6;
        $bag = $base->isRedirection() ? session('errors') : null;
        $errors = $bag instanceof ViewErrorBag ? $bag->all() : array_map('strval', Arr::flatten((array) $bag));
        expect($errors)->toBe([], 'Lỗi validation: '.implode(' · ', $errors));
        $runs[] = ['ms' => $ms, 'queries' => $counter['queries'], 'kb' => strlen($content) / 1024, 'status' => $base->getStatusCode(), 'sql_ms' => $counter['sql_ms'], 'log' => $counter['log']];
    }
    usort($runs, fn (array $a, array $b): int => $a['ms'] <=> $b['ms']);

    return $runs[1];
}

it('một ngày thao tác Admin: 1.000 sản phẩm, 3.000 SKU, 120 đơn', function () {
    set_time_limit(0);
    // 120 đơn từ một IP trong vài chục giây vượt rate limit storefront: bỏ qua như khi chạy tải (VANI_LOAD_TEST_IPS).
    config(['vanishop.load_test.bypass_ips' => ['127.0.0.1']]);
    $seedStarted = hrtime(true);

    // ---------- Dữ liệu: danh mục, thương hiệu, tuỳ chọn dùng chung ----------
    $brands = Brand::factory()->count(5)->create();
    $colors = OptionValue::factory()->color()->count(12)->create();
    $sizes = collect(['S', 'M', 'L'])->map(fn (string $code, int $index) => OptionValue::factory()->size()->create(['code' => $code, 'position' => $index]));
    $location = Location::query()->first() ?? I::location(['code' => 'WH-MAIN']);
    $base = T::seed(fn () => app(BulkBasePrice::class)->baseList());

    $styles = [];
    $variantIds = [];
    T::seed(function () use (&$styles, &$variantIds, $brands, $colors, $sizes): void {
        for ($i = 1; $i <= BENCH_PRODUCTS; $i++) {
            $style = T::product($brands[$i % 5]->id, ['style_code' => sprintf('BX%04d', $i), 'slug' => "bench-{$i}", 'name' => "Áo thun mẫu {$i}"]);
            $color = $colors[$i % 12];
            app(ProductOptions::class)->syncAxes($style, [
                ['option_type_id' => $color->option_type_id, 'value_ids' => [$color->id]],
                ['option_type_id' => $sizes[0]->option_type_id, 'value_ids' => $sizes->pluck('id')->all()],
            ]);
            foreach ($sizes as $size) {
                $variantIds[] = app(ProductOptions::class)->createVariant($style, [$color->id, $size->id], ['sku' => "{$style->style_code}-{$size->code}"])->id;
            }
            $styles[] = $style;
        }
    });
    foreach (array_chunk($variantIds, 500) as $chunk) {
        DB::table('prices')->insert(array_map(fn (int $id): array => ['price_list_id' => $base->id, 'variant_id' => $id, 'amount' => 199_000 + ($id % 20) * 10_000, 'compare_at_amount' => null, 'min_qty' => 1, 'created_at' => now(), 'updated_at' => now()], $chunk));
        DB::table('stock_levels')->insert(array_map(fn (int $id): array => ['location_id' => $location->id, 'variant_id' => $id, 'on_hand' => 20 + $id % 30, 'reserved' => 0, 'safety_stock' => 0, 'created_at' => now(), 'updated_at' => now()], $chunk));
    }

    // ---------- 120 đơn COD qua Storefront API (đặt đơn thật: giữ hàng, thanh toán, khách) ----------
    $api = '/api/storefront/v1';
    for ($n = 0; $n < BENCH_ORDERS; $n++) {
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        foreach ([$variantIds[($n * 7) % count($variantIds)], $variantIds[($n * 13 + 1) % count($variantIds)]] as $variantId) {
            $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $variantId, 'quantity' => 1], $headers)->assertOk();
        }
        $total = $this->postJson("{$api}/checkout/{$cart}/quote", C::orderPayload(), $headers)->assertOk()->json('data.total.amount');
        $phone = sprintf('09%08d', 10_000_000 + $n);
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['contact' => ['phone' => $phone, 'email' => "bench{$n}@example.com"], 'expected_total' => $total]), [...$headers, 'Idempotency-Key' => "bench-order-{$n}"])->assertCreated();
    }
    $sale = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);
    $seedSeconds = (hrtime(true) - $seedStarted) / 1e9;
    expect(DB::table('variants')->count())->toBeGreaterThanOrEqual(BENCH_PRODUCTS * 3)
        ->and(Order::query()->withoutGlobalScopes()->count())->toBe(BENCH_ORDERS);

    // ---------- Nhân viên ----------
    // Như production: job hàng đợi (đồng bộ giá niêm yết, chỉ mục tìm kiếm, gửi tin…) chạy nền, không tính vào thời gian
    // nhân viên chờ — test mặc định dùng `sync` sẽ cộng chúng vào request.
    config(['queue.default' => 'database']);
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'orders.view', 'orders.manage', 'fulfillment.view', 'fulfillment.manage',
        'inventory.view', 'inventory.adjust', 'pricing.view', 'pricing.manage', 'payments.view', 'customers.view', 'returns.view']), 'staff');
    $admin = '/admin';
    $orderIds = Order::query()->withoutGlobalScopes()->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    $middle = $styles[BENCH_PRODUCTS / 2];
    $styleIds = array_map(fn (Style $style): int => $style->id, $styles);
    $styleVariants = DB::table('variants')->where('style_id', $middle->id)->orderBy('id')->get(['id', 'sku']);

    /**
     * [nhóm, thao tác, số lần/ngày, request]
     *
     * @var list<array{0: string, 1: string, 2: int, 3: Closure}>
     */
    $steps = [
        ['Tổng quan', 'Mở Tổng quan (việc cần làm)', 15, fn () => $this->get("{$admin}")],
        ['Đơn hàng', 'Danh sách đơn — cần xử lý', 40, fn () => $this->get("{$admin}/orders/orders?actionable=1")],
        ['Đơn hàng', 'Danh sách đơn 100/trang', 10, fn () => $this->get("{$admin}/orders/orders?per_page=100")],
        ['Đơn hàng', 'Tìm đơn theo SĐT', 30, fn () => $this->get("{$admin}/orders/orders?q=0910000050")],
        ['Đơn hàng', 'Mở chi tiết đơn', BENCH_ORDERS, fn (int $i) => $this->get("{$admin}/orders/orders/{$orderIds[$i]}")],
        ['Đơn hàng', 'Xác nhận hàng loạt 30 đơn', 4, fn (int $i) => $this->post("{$admin}/orders/orders/bulk-confirm", ['ids' => array_slice($orderIds, $i * 30, 30)])],
        ['Giao hàng', 'Tạo vận đơn hàng loạt 30 đơn', 4, fn (int $i) => $this->post("{$admin}/fulfillment/shipments/bulk-create", ['ids' => array_slice($orderIds, $i * 30, 30)])],
        ['Giao hàng', 'In phiếu giao 30 đơn', 4, fn (int $i) => $this->get("{$admin}/orders/orders/print?".http_build_query(['ids' => array_slice($orderIds, $i * 30, 30)]))],
        ['Giao hàng', 'Danh sách vận đơn', 20, fn () => $this->get("{$admin}/fulfillment/shipments")],
        ['Sản phẩm', 'Danh sách sản phẩm', 60, fn () => $this->get("{$admin}/catalog/products")],
        ['Sản phẩm', 'Danh sách sản phẩm 100/trang', 10, fn () => $this->get("{$admin}/catalog/products?per_page=100")],
        ['Sản phẩm', 'Tìm sản phẩm theo mã', 60, fn (int $i) => $this->get("{$admin}/catalog/products?q=BX0".(500 + $i))],
        ['Sản phẩm', 'Mở form sửa sản phẩm', 30, fn () => $this->get("{$admin}/catalog/products/{$middle->id}/edit")],
        ['Sản phẩm', 'Lưu sản phẩm + ma trận (giá, tồn 3 SKU)', 30, function (int $i) use ($admin, $middle, $styleVariants, $colors, $sizes) {
            $style = Style::query()->find($middle->id);
            $color = $colors[(BENCH_PRODUCTS / 2 + 1) % 12]; // sản phẩm thứ 501 (đánh số từ 1)

            return $this->put("{$admin}/catalog/products/{$middle->id}", [
                'style_code' => $style->style_code, 'slug' => $style->slug, 'status' => 'active', 'lock_version' => $style->lock_version,
                'translations' => ['vi' => ['name' => "Áo thun mẫu sửa {$i}"]],
                'variants' => ['axes' => [['option_type_id' => $color->option_type_id, 'value_ids' => [$color->id]], ['option_type_id' => $sizes[0]->option_type_id, 'value_ids' => $sizes->pluck('id')->all()]],
                    'cells' => $sizes->values()->map(fn ($size, int $k): array => ['values' => [$color->id, $size->id], 'enabled' => true, 'status' => 'active', 'sku' => $styleVariants[$k]->sku, 'barcode' => null, 'weight_gram' => 300,
                        'fields' => ['pricing' => ['price' => 250_000 + $i * 1000, 'compare_at' => null], 'inventory' => ['on_hand' => 30 + $i]]])->all()],
            ]);
        }],
        ['Tồn kho', 'Lưới tồn', 30, fn () => $this->get("{$admin}/inventory/stock")],
        ['Tồn kho', 'Lưới tồn 100/trang', 10, fn () => $this->get("{$admin}/inventory/stock?per_page=100")],
        ['Tồn kho', 'Sửa tồn một ô', 80, fn (int $i) => $this->post("{$admin}/inventory/stock", ['location_id' => $location->id, 'variant_id' => $variantIds[$i], 'action' => 'count', 'quantity' => 40 + $i, 'reason' => 'Kiểm kê'])],
        ['Tồn kho', 'Lưu 50 ô tồn một lần', 4, fn (int $i) => $this->post("{$admin}/inventory/stock/batch", ['reason' => 'Kiểm kê', 'changes' => array_map(fn (int $id): array => ['location_id' => $location->id, 'variant_id' => $id, 'on_hand' => 50, 'expected' => (int) DB::table('stock_levels')->where('variant_id', $id)->value('on_hand')], array_slice($variantIds, 100 + $i * 50, 50))])],
        ['Tồn kho', 'Xuất file tồn (3.000 SKU)', 1, fn () => $this->get("{$admin}/inventory/stock/export")],
        ['Giá', 'Đổi giá niêm yết hàng loạt 200 sản phẩm', 5, fn (int $i) => $this->post("{$admin}/pricing/base-prices/bulk", ['ids' => array_slice($styleIds, $i * 200, 200), 'mode' => 'percent_up', 'value' => 5])],
        // Đưa sản phẩm vào bảng sale: chọn 200 sản phẩm ở danh sách → "Đưa vào bảng giá…" → trang làm việc → xem trước → áp.
        ['Giá', 'Sale 200 sản phẩm: mở trang làm việc', 5, fn (int $i) => $this->followingRedirects()->post("{$admin}/pricing/price-lists/add-products", ['ids' => array_slice($styleIds, $i * 200, 200), 'price_list_id' => $sale->id])],
        ['Giá', 'Sale 200 sản phẩm: xem trước giá −20%', 5, fn (int $i) => $this->postJson("{$admin}/pricing/price-lists/{$sale->id}/bulk/preview", ['scope' => 'styles', 'style_ids' => array_slice($styleIds, $i * 200, 200), 'mode' => 'percent', 'value' => 20, 'round_to' => 1000])],
        ['Giá', 'Sale 200 sản phẩm: áp giá −20%', 5, fn (int $i) => $this->post("{$admin}/pricing/price-lists/{$sale->id}/bulk", ['scope' => 'styles', 'style_ids' => array_slice($styleIds, $i * 200, 200), 'mode' => 'percent', 'value' => 20, 'round_to' => 1000])],
        ['Giá', 'Trang làm việc bảng sale', 15, fn () => $this->get("{$admin}/pricing/price-lists/{$sale->id}/prices")],
        ['Giá', 'Xem trước giảm 10% cả bảng sale', 3, fn () => $this->postJson("{$admin}/pricing/price-lists/{$sale->id}/bulk/preview", ['scope' => 'all', 'mode' => 'percent', 'value' => 10, 'round_to' => 1000])],
        ['Giá', 'Áp giảm 10% cả bảng sale', 1, fn () => $this->post("{$admin}/pricing/price-lists/{$sale->id}/bulk", ['scope' => 'all', 'mode' => 'percent', 'value' => 10, 'round_to' => 1000])],
        ['Giá', 'Xuất file bảng sale', 1, fn () => $this->get("{$admin}/pricing/price-lists/{$sale->id}/export")],
        ['Khác', 'Danh sách thanh toán', 15, fn () => $this->get("{$admin}/payment/payments")],
        ['Khác', 'Danh sách khách hàng', 15, fn () => $this->get("{$admin}/customers")],
        ['Khác', 'Danh sách đổi/trả', 5, fn () => $this->get("{$admin}/returns/returns")],
    ];

    $rows = [];
    foreach ($steps as [$group, $label, $perDay, $request]) {
        $result = benchMeasure($request);
        expect($result['status'])->toBeLessThan(400, "{$label}: HTTP {$result['status']}");
        $rows[] = [$group, $label, $perDay, $result['ms'], $result['queries'], $result['kb'], $result['sql_ms']];
        if (getenv('BENCH_TRACE') && str_contains($label, (string) getenv('BENCH_TRACE'))) {
            file_put_contents(storage_path('logs/admin-benchmark-trace.log'), "== {$label}
".implode('
', $result['log']).'

', FILE_APPEND);
        }
    }

    $report = ['# Đo thao tác Admin — '.now()->format('Y-m-d H:i'), '', sprintf('Dữ liệu: %d sản phẩm, %d SKU, %d đơn (dựng trong %.0f s). DB: %s.', BENCH_PRODUCTS, count($variantIds), BENCH_ORDERS, $seedSeconds, DB::getDriverName()), '',
        '| Nhóm | Thao tác | Lần/ngày | ms (trung vị) | SQL ms | Truy vấn | KB | Tổng ms/ngày |', '|---|---|---:|---:|---:|---:|---:|---:|'];
    $total = 0;
    foreach ($rows as [$group, $label, $perDay, $ms, $queries, $kb, $sqlMs]) {
        $total += $ms * $perDay;
        $report[] = sprintf('| %s | %s | %d | %.0f | %.0f | %d | %.0f | %.0f |', $group, $label, $perDay, $ms, $sqlMs, $queries, $kb, $ms * $perDay);
    }
    $report[] = '';
    $report[] = sprintf('Tổng thời gian chờ máy chủ trong ngày: **%.1f s** (%d request).', $total / 1000, array_sum(array_column($rows, 2)));
    file_put_contents(storage_path('logs/admin-benchmark.md'), implode("\n", $report)."\n");
    fwrite(STDOUT, "\n".implode("\n", $report)."\n");

    // Ngân sách chống hồi quy: trang danh sách/chi tiết không N+1 theo số dòng; ghi hàng loạt tuyến tính có trần.
    foreach ($rows as [$group, $label, , $ms, $queries]) {
        expect($queries)->toBeLessThan(str_contains($label, 'hàng loạt') || str_contains($label, '50 ô') || str_contains($label, 'Áp giảm') || str_contains($label, '200 sản phẩm') ? 3000 : 150, "{$label}: {$queries} truy vấn");
    }
})->group('performance');
