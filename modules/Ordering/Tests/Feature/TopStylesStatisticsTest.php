<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ordering\Contracts\OrderStatistics;

/*
| OrderStatistics::topStyles (0.3.43): style bán chạy theo số lượng — nguồn của khối "Bán chạy" (vani.storefront-extras).
*/

function topStylesOrder(string $placedAt, array $lines, string $status = 'confirmed', string $source = 'web'): void
{
    $id = DB::table('orders')->insertGetId([
        'public_id' => strtoupper((string) Str::ulid()), 'number' => 'TS'.Str::random(6), 'source' => $source, 'currency_code' => 'VND',
        'order_status' => $status, 'payment_status' => 'pending', 'fulfillment_status' => 'unfulfilled', 'payment_method' => 'cod',
        'subtotal_amount' => 0, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'total_amount' => 0,
        'customer_snapshot' => '{}', 'shipping_address' => '{}', 'shipping_method' => '{}', 'reservation_key' => (string) Str::ulid(),
        'placed_at' => $placedAt, 'created_at' => $placedAt, 'updated_at' => $placedAt,
    ]);
    foreach ($lines as [$styleId, $quantity, $cancelled]) {
        DB::table('order_lines')->insert([
            'order_id' => $id, 'variant_id' => 1, 'sku' => 'SKU', 'product_name' => 'SP', 'size_code' => 'M',
            'quantity' => $quantity, 'cancelled_quantity' => $cancelled, 'unit_amount' => 100, 'subtotal_amount' => 100, 'discount_amount' => 0, 'total_amount' => 100,
            'tax_rate_bp' => 0, 'tax_amount' => 0, 'meta' => $styleId === null ? null : json_encode(['style_id' => $styleId]), 'created_at' => $placedAt, 'updated_at' => $placedAt,
        ]);
    }
}

it('xếp style theo số lượng còn lại sau huỷ một phần, bỏ đơn huỷ, đơn đổi hàng, ngoài khoảng và dòng không có style', function () {
    topStylesOrder('2026-10-10 03:00:00', [[7, 2, 0], [9, 1, 2]]);   // style 9: còn 1 sau khi huỷ 2
    topStylesOrder('2026-10-11 03:00:00', [[9, 1, 0], [11, 2, 0], [null, 50, 0]]);   // hoà 2: sắp theo id số (7, 9, 11), không theo chuỗi
    topStylesOrder('2026-10-11 04:00:00', [[11, 40, 0]], status: 'cancelled');
    topStylesOrder('2026-10-11 05:00:00', [[12, 30, 0]], source: 'exchange');
    topStylesOrder('2026-08-01 03:00:00', [[13, 99, 0]]);

    $top = app(OrderStatistics::class)->topStyles(new DateTimeImmutable('2026-10-01T00:00:00Z'), new DateTimeImmutable('2026-10-14T00:00:00Z'), 10);

    expect($top)->toBe([
        ['style_id' => 7, 'quantity' => 2],
        ['style_id' => 9, 'quantity' => 2],
        ['style_id' => 11, 'quantity' => 2],
    ])->and(app(OrderStatistics::class)->topStyles(new DateTimeImmutable('2026-10-01T00:00:00Z'), new DateTimeImmutable('2026-10-14T00:00:00Z'), 1))->toHaveCount(1);
});
