<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\CostPrice\CostPriceServiceProvider;
use Plugin\Reports\Infrastructure\Reports\GrossProfitByProductReport;
use Plugin\Reports\Infrastructure\Reports\GrossProfitDailyReport;
use Plugin\Reports\Infrastructure\Reports\ReturnReasonsReport;
use Plugin\Reports\Infrastructure\Reports\ReturnsByProductReport;
use Plugin\Reports\ReportsServiceProvider;

require_once __DIR__.'/../../../../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';
require_once __DIR__.'/../../../../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A10: lợi nhuận gộp (giá vốn qua hook `vani.pricing.unit_costs` — plugin vani.cost-price), SKU thiếu giá vốn báo
| rõ, không coi là 0; đổi trả theo SKU/lý do; COD giao thất bại.
*/

function enableReportPlugin(string $id, string $provider): void
{
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($id) {
        app(PluginManager::class)->install($id);
        app(PluginManager::class)->enable($id);
    });
    app()->register($provider);
}

/**
 * @param  list<array{0: int, 1: int, 2: int}>  $lines  [variant id, SL, thành tiền]
 * @return list<int> id dòng đơn
 */
function grossProfitOrder(string $placedAt, array $lines, string $status = 'confirmed'): array
{
    static $n = 0;
    $n++;
    $orderId = DB::table('orders')->insertGetId([
        'public_id' => strtoupper(Str::ulid()), 'number' => 'GP'.$n, 'source' => 'web', 'currency_code' => 'VND',
        'order_status' => $status, 'payment_status' => 'pending', 'fulfillment_status' => 'unfulfilled', 'payment_method' => 'cod',
        'subtotal_amount' => 0, 'discount_amount' => 0, 'shipping_amount' => 30_000, 'tax_amount' => 0, 'total_amount' => 0,
        'customer_snapshot' => '{}', 'shipping_address' => '{}', 'shipping_method' => '{}', 'reservation_key' => 'gp'.$n,
        'placed_at' => $placedAt, 'created_at' => $placedAt, 'updated_at' => $placedAt,
    ]);

    return array_map(fn (array $line): int => DB::table('order_lines')->insertGetId([
        'order_id' => $orderId, 'variant_id' => $line[0], 'sku' => "SKU-{$line[0]}", 'product_name' => "Áo {$line[0]}",
        'quantity' => $line[1], 'unit_amount' => intdiv($line[2], $line[1]), 'subtotal_amount' => $line[2], 'discount_amount' => 0, 'total_amount' => $line[2],
        'tax_rate_bp' => 0, 'tax_amount' => 0, 'created_at' => $placedAt, 'updated_at' => $placedAt,
    ]), $lines);
}

function grossProfitReturn(int $orderLineId, int $variantId, int $quantity, string $reason, string $status = 'requested', string $at = '2026-10-02 03:00:00'): void
{
    static $n = 0;
    $n++;
    $orderId = (int) DB::table('order_lines')->where('id', $orderLineId)->value('order_id');
    $id = DB::table('return_requests')->insertGetId([
        'public_id' => strtoupper(Str::ulid()), 'number' => "R{$n}", 'order_id' => $orderId, 'status' => $status, 'reason_code' => $reason,
        'source' => 'customer', 'refund_amount' => $quantity * 100_000, 'currency_code' => 'VND', 'created_at' => $at, 'updated_at' => $at,
    ]);
    DB::table('return_lines')->insert(['return_request_id' => $id, 'order_line_id' => $orderLineId, 'variant_id' => $variantId, 'quantity' => $quantity, 'refund_amount' => $quantity * 100_000, 'created_at' => $at, 'updated_at' => $at]);
}

beforeEach(function () {
    enableReportPlugin(ReportsServiceProvider::ID, ReportsServiceProvider::class);
    [$s, $m] = P::variants(T::product());
    [$this->a, $this->b] = [$a, $b] = [$s->id, $m->id];
    // 01/10 và 02/10 giờ VN; đơn huỷ và đơn ngoài kỳ không tính.
    $this->lines = [
        ...grossProfitOrder('2026-10-01 03:00:00', [[$a, 2, 400_000], [$b, 1, 150_000]]),
        ...grossProfitOrder('2026-10-02 03:00:00', [[$a, 1, 200_000]]),
        ...grossProfitOrder('2026-10-02 04:00:00', [[$a, 5, 1_000_000]], status: 'cancelled'),
        ...grossProfitOrder('2026-09-01 03:00:00', [[$b, 1, 150_000]]),
    ];
    $this->period = new ReportPeriod(new DateTimeImmutable('2026-10-01T00:00:00+07:00'), new DateTimeImmutable('2026-10-03T00:00:00+07:00'));
    $this->summary = fn (ReportResult $result): array => collect($result->summary)->mapWithKeys(fn (Metric $metric) => [$metric->label => [$metric->value, $metric->hint]])->all();
});

it('chưa có nguồn giá vốn: không tính lợi nhuận, báo rõ thiếu giá vốn', function () {
    $result = app(GrossProfitByProductReport::class)->run($this->period);

    expect($result->table->rows)->toHaveCount(2)
        ->and($result->table->rows[0])->toMatchArray(['sku' => "SKU-{$this->a}", 'quantity' => 3, 'revenue' => 600_000, 'cost' => null, 'profit' => null, 'note' => 'Thiếu giá vốn'])
        ->and(($this->summary)($result)['Lợi nhuận gộp'][0])->toBe(0)
        ->and(($this->summary)($result)['SKU thiếu giá vốn'])->toBe([2, 'Chưa có nguồn giá vốn — bật plugin Giá vốn và nhập giá vốn theo SKU']);
});

it('có giá vốn (vani.cost-price): lợi nhuận theo SKU và theo ngày; SKU thiếu giá vốn tách riêng', function () {
    enableReportPlugin(CostPriceServiceProvider::ID, CostPriceServiceProvider::class);
    DB::table('plg_cost_prices')->insert(['variant_id' => $this->a, 'amount' => 120_000, 'created_at' => now(), 'updated_at' => now()]);

    $byProduct = app(GrossProfitByProductReport::class)->run($this->period);
    expect($byProduct->table->rows[0])->toMatchArray(['sku' => "SKU-{$this->a}", 'quantity' => 3, 'revenue' => 600_000, 'cost' => 360_000, 'profit' => 240_000, 'margin' => 40.0, 'note' => ''])
        ->and($byProduct->table->rows[1])->toMatchArray(['sku' => "SKU-{$this->b}", 'cost' => null, 'note' => 'Thiếu giá vốn'])
        ->and(($this->summary)($byProduct))->toMatchArray([
            'Lợi nhuận gộp' => [240_000, 'Biên 40%'], 'Giá vốn' => [360_000, null],
            'SKU thiếu giá vốn' => [1, 'Doanh thu 150.000 ₫ chưa tính lợi nhuận'],
        ]);

    $daily = app(GrossProfitDailyReport::class)->run($this->period);
    expect(array_map(fn (array $row) => [$row['date'], $row['revenue'], $row['cost'], $row['profit'], $row['uncosted']], $daily->table->rows))->toBe([
        ['2026-10-01', 400_000, 240_000, 160_000, 150_000],
        ['2026-10-02', 200_000, 120_000, 80_000, 0],
    ])->and($daily->chart->values)->toBe([160_000, 80_000]);
});

it('đổi trả: theo SKU (tỷ lệ so với số bán trong kỳ), theo lý do (tách lỗi do cửa hàng, phí ship trả shop chịu — L10); bỏ yêu cầu bị từ chối; COD giao thất bại', function () {
    grossProfitReturn($this->lines[0], $this->a, 1, 'wrong_size');
    grossProfitReturn($this->lines[2], $this->a, 1, 'defective', status: 'resolved');
    DB::table('return_requests')->where('reason_code', 'defective')->update(['return_shipping_fee' => 30_000, 'return_shipping_payer' => 'store']);
    grossProfitReturn($this->lines[1], $this->b, 1, 'wrong_size', status: 'rejected');
    $location = I::location();
    foreach ([['delivered', 300_000], ['returned', 250_000], ['returning', 100_000], ['delivered', 0]] as $i => [$status, $cod]) {
        DB::table('shipments')->insert(['public_id' => strtoupper(Str::ulid()), 'order_id' => DB::table('orders')->value('id'), 'location_id' => $location->id, 'carrier_code' => 'manual',
            'cod_amount' => $cod, 'currency_code' => 'VND', 'status' => $status, 'created_at' => '2026-10-02 03:00:00', 'updated_at' => '2026-10-02 03:00:00']);
    }

    $byProduct = app(ReturnsByProductReport::class)->run($this->period);
    expect($byProduct->table->rows)->toBe([['sku' => "SKU-{$this->a}", 'product' => "Áo {$this->a}", 'sold' => 3, 'returned' => 2, 'rate' => 66.7, 'refund' => 200_000]])
        ->and(($this->summary)($byProduct)['Tỷ lệ trả'][0])->toBe(50.0);

    $reasons = app(ReturnReasonsReport::class)->run($this->period);
    expect(collect($reasons->table->rows)->mapWithKeys(fn (array $row) => [$row['fault'] => [$row['requests'], $row['quantity'], $row['share'], $row['store_shipping_fee']]])->sortKeys()->all())
        ->toBe(['Lỗi do cửa hàng' => [1, 1, 50.0, 30_000], 'Phía khách' => [1, 1, 50.0, 0]])
        ->and(array_column($reasons->table->rows, 'reason'))->each->not->toBe('')
        ->and(($this->summary)($reasons))->toMatchArray([
            'Lỗi do cửa hàng' => [50.0, '1 yêu cầu'],
            'Phí ship trả shop chịu' => [30_000, null],
            'COD giao thất bại' => [2, 'Trên 3 vận đơn COD · đã giao 1'],
            'Tiền COD không thu được' => [350_000, null],
        ]);
});
