<?php

declare(strict_types=1);

namespace Modules\Ordering\Tests\Fixtures;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\OrderReader;

/**
 * Đơn mẫu cho contract test của điểm mở rộng về đơn (OrderRiskSignal, ConfirmationChannel, OrderStatusAction…): chèn thẳng
 * bảng `orders` — closure của contract suite chạy ngoài test case nên không đặt đơn qua API được.
 */
final class ContractOrders
{
    /**
     * @param  array<string, mixed>  $overrides  cột của `orders`
     */
    public static function seed(string $phone = '0912345678', array $overrides = []): int
    {
        static $n = 0;
        $n++;
        $now = now();

        return (int) DB::table('orders')->insertGetId([
            'public_id' => strtoupper((string) Str::ulid()), 'number' => 'CT'.str_pad((string) $n, 6, '0', STR_PAD_LEFT), 'source' => 'web',
            'currency_code' => 'VND', 'order_status' => 'pending', 'payment_status' => 'cod_pending', 'fulfillment_status' => 'unfulfilled',
            'payment_method' => 'cod', 'subtotal_amount' => 300_000, 'discount_amount' => 0, 'shipping_amount' => 30_000, 'tax_amount' => 0,
            'total_amount' => 330_000, 'customer_snapshot' => json_encode(['full_name' => 'Khách thử', 'phone' => $phone]),
            'shipping_address' => json_encode(['full_name' => 'Khách thử', 'phone' => $phone, 'province_code' => '01', 'street_line' => '1 Phố Huế']),
            'shipping_method' => '{}', 'reservation_key' => 'ct-'.$n, 'placed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ...$overrides,
        ]);
    }

    public static function data(int $orderId): OrderData
    {
        return app(OrderReader::class)->find($orderId) ?? throw new \RuntimeException("Không có đơn {$orderId}.");
    }
}
