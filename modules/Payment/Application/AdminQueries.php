<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\Domain\RefundStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Http\ListSort;
use Modules\Shared\Support\DeferredPage;

/**
 * Truy vấn đọc cho màn hình Admin của Payment — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /**
     * Danh sách khoản thanh toán: lọc trạng thái, theo đơn (`$orderId` null = mọi đơn, 0 = không đơn nào), id của plugin.
     *
     * @param  list<int>|null  $ids
     * @return LengthAwarePaginator<int, Payment>
     */
    public function payments(string $status, ?int $orderId, ?array $ids, int $perPage, ?ListSort $sort = null): LengthAwarePaginator
    {
        return DeferredPage::paginate(Payment::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($orderId !== null, fn ($query) => $query->where('order_id', $orderId))
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($sort !== null, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('id')), $perPage); // trang sâu nhanh (L54)
    }

    /**
     * Khoản hoàn chờ chuyển trả hoặc đang chuyển trả (khu "Hoàn tiền chờ xử lý").
     *
     * @return Collection<int, Refund>
     */
    public function refundsAwaitingPayout(?int $orderId = null): Collection
    {
        return Refund::query()->whereIn('payment_id', Payment::query()->select('id')->when($orderId !== null, fn ($query) => $query->where('order_id', $orderId)))
            ->whereIn('status', [RefundStatus::Requested->value, RefundStatus::PayingOut->value])->orderBy('id')->get();
    }

    /**
     * @param  list<int>  $paymentIds
     * @return array<int, int>
     */
    public function paymentOrders(array $paymentIds): array
    {
        return Payment::query()->whereKey($paymentIds)->pluck('order_id', 'id')->map(fn ($id): int => (int) $id)->all();
    }

    public function paymentExists(int $paymentId): bool
    {
        return Payment::query()->whereKey($paymentId)->exists();
    }
}
