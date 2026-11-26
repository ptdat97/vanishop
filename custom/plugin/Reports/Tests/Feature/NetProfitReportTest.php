<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Reports\Infrastructure\Reports\NetProfitDailyReport;
use Plugin\Reports\ReportsServiceProvider;

/*
| Lợi nhuận sau trả hàng và phí giao theo kỳ bán của đơn gốc (reference-parity L43): hàng trả tính về ngày đặt của đơn kể cả
| khi trả ở kỳ sau; hàng trả về kho trả lại giá vốn, không về kho thì mất; phí hãng, ship trả hàng cửa hàng chịu trừ vào lợi
| nhuận; dòng thiếu giá vốn không vào lợi nhuận.
*/

function netProfitLine(string $placedAt, int $quantity, int $total, ?int $unitCost): int
{
    static $n = 0;
    $n++;
    $orderId = DB::table('orders')->insertGetId([
        'public_id' => strtoupper(Str::ulid()), 'number' => 'NP'.$n, 'source' => 'web', 'currency_code' => 'VND',
        'order_status' => 'confirmed', 'payment_status' => 'pending', 'fulfillment_status' => 'unfulfilled', 'payment_method' => 'cod',
        'subtotal_amount' => $total, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'total_amount' => $total,
        'customer_snapshot' => '{}', 'shipping_address' => '{}', 'shipping_method' => '{}', 'reservation_key' => 'np'.$n,
        'placed_at' => $placedAt, 'created_at' => $placedAt, 'updated_at' => $placedAt,
    ]);

    return DB::table('order_lines')->insertGetId([
        'order_id' => $orderId, 'variant_id' => 1, 'product_id' => 1, 'sku' => 'SKU-1', 'product_name' => 'Áo', 'quantity' => $quantity,
        'unit_amount' => intdiv($total, $quantity), 'subtotal_amount' => $total, 'discount_amount' => 0, 'total_amount' => $total, 'tax_rate_bp' => 0, 'tax_amount' => 0,
        'meta' => $unitCost === null ? null : json_encode(['unit_cost_at_sale' => $unitCost]), 'created_at' => $placedAt, 'updated_at' => $placedAt,
    ]);
}

function netProfitReturn(int $lineId, int $quantity, int $refund, ?int $restockLocation, string $at, ?int $storeShipping = null): void
{
    $orderId = (int) DB::table('order_lines')->where('id', $lineId)->value('order_id');
    $id = DB::table('return_requests')->insertGetId([
        'public_id' => strtoupper(Str::ulid()), 'number' => 'NR'.$lineId, 'order_id' => $orderId, 'status' => $storeShipping === null ? 'received' : 'resolved',
        'reason_code' => 'size', 'source' => 'customer', 'refund_amount' => $refund, 'currency_code' => 'VND',
        'return_shipping_fee' => $storeShipping ?? 0, 'return_shipping_payer' => $storeShipping === null ? null : 'store', 'created_at' => $at, 'updated_at' => $at,
    ]);
    DB::table('return_lines')->insert(['return_request_id' => $id, 'order_line_id' => $lineId, 'variant_id' => 1, 'quantity' => $quantity, 'refund_amount' => $refund,
        'restock_location_id' => $restockLocation, 'created_at' => $at, 'updated_at' => $at]);
}

function netProfitShipment(int $lineId, ?int $fee, int $locationId): void
{
    DB::table('shipments')->insert(['public_id' => strtoupper(Str::ulid()), 'order_id' => (int) DB::table('order_lines')->where('id', $lineId)->value('order_id'), 'location_id' => $locationId,
        'carrier_code' => 'ghn', 'currency_code' => 'VND', 'status' => 'delivered', 'cod_amount' => 0, 'shipped_at' => now(), 'actual_carrier_fee' => $fee, 'created_at' => now(), 'updated_at' => now()]);
}

it('tính lợi nhuận ròng theo ngày đặt: hoàn tiền, giá vốn hàng về kho, phí hãng, ship trả hàng; trả ở kỳ sau vẫn tính về ngày đặt', function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function (): void {
        app(PluginManager::class)->install(ReportsServiceProvider::ID);
        app(PluginManager::class)->enable(ReportsServiceProvider::ID);
    });
    app()->register(ReportsServiceProvider::class);
    $warehouse = DB::table('locations')->insertGetId(['code' => 'K1', 'name' => 'Kho', 'type' => 'warehouse', 'created_at' => now(), 'updated_at' => now()]);
    $day = '2026-10-01 03:00:00'; // 10:00 giờ VN

    $restocked = netProfitLine($day, 2, 400_000, 120_000);   // trả 1, về kho
    $damaged = netProfitLine($day, 1, 300_000, 100_000);     // trả 1, không về kho, cửa hàng chịu ship 30k
    $uncosted = netProfitLine($day, 1, 200_000, null);       // thiếu giá vốn: hoàn tiền tách riêng
    netProfitReturn($restocked, 1, 200_000, $warehouse, '2026-11-15 03:00:00'); // trả ở tháng sau
    netProfitReturn($damaged, 1, 300_000, null, '2026-10-05 03:00:00', storeShipping: 30_000);
    netProfitReturn($uncosted, 1, 200_000, $warehouse, '2026-10-05 03:00:00');
    netProfitShipment($restocked, 25_000, $warehouse);
    netProfitShipment($damaged, null, $warehouse); // chưa khớp bảng kê

    $result = app(NetProfitDailyReport::class)->run(new ReportPeriod(new DateTimeImmutable('2026-10-01 00:00:00', new DateTimeZone('Asia/Ho_Chi_Minh')), new DateTimeImmutable('2026-10-02 00:00:00', new DateTimeZone('Asia/Ho_Chi_Minh')), 'Asia/Ho_Chi_Minh'));
    $row = $result->table->rows[0];

    // Doanh thu có giá vốn 700k, giá vốn 340k; hoàn 500k (dòng có giá vốn); về kho trả lại 120k; phí hãng 25k; ship trả 30k.
    expect($row)->toMatchArray(['date' => '2026-10-01', 'revenue' => 700_000, 'cost' => 340_000, 'refunds' => 500_000, 'restocked_cost' => 120_000, 'carrier_fees' => 25_000, 'return_shipping' => 30_000])
        ->and($row['profit'])->toBe(700_000 - 500_000 - (340_000 - 120_000) - 25_000 - 30_000)
        ->and($row['return_rate'])->toBe(75.0) // 3 trả / 4 bán
        ->and($result->summary[0]->hint)->toContain('1')
        ->and($result->summary[3]->value)->toBe(200_000);
});
