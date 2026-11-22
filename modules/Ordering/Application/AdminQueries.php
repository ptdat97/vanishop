<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Database\Eloquent\Collection;
use Modules\Ordering\Persistence\Models\Order;

/**
 * Truy vấn đọc cho màn hình Admin của Ordering — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /** Giai đoạn vận hành của đơn (thanh tiến trình trang đơn). */
    public function stage(int $orderId): string
    {
        return (string) Order::query()->whereKey($orderId)->value('stage');
    }

    /**
     * @param  array<int|string, mixed>  $ids
     * @return Collection<int, Order>
     */
    public function ordersByIds(array $ids): Collection
    {
        return Order::query()->whereIn('id', array_map('intval', $ids))->get();
    }

    /**
     * Mã phương thức thanh toán đã có trên đơn (bộ lọc danh sách đơn).
     *
     * @return list<string>
     */
    public function usedPaymentMethods(): array
    {
        return Order::query()->distinct()->pluck('payment_method')->filter()->map(fn ($code): string => (string) $code)->values()->all();
    }

    /**
     * Đơn kèm dòng, theo đúng thứ tự id truyền vào (in phiếu theo thứ tự nhân viên chọn).
     *
     * @param  list<int>  $ids
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function ordersWithLinesInOrder(array $ids): \Illuminate\Support\Collection
    {
        return Order::query()->with('lines')->whereIn('id', $ids)->get()->sortBy(fn (Order $order): int => (int) array_search($order->id, $ids, true));
    }
}
