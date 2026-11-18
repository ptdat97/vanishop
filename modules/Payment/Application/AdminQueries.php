<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\Domain\RefundStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Http\ListSort;

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
        return Payment::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($orderId !== null, fn ($query) => $query->where('order_id', $orderId))
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($sort !== null, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('id'))->paginate($perPage)->withQueryString();
    }

    /**
     * Khoản hoàn chờ chuyển trả hoặc đang chuyển trả (khu "Hoàn tiền chờ xử lý").
     *
     * @return Collection<int, Refund>
     */
    public function refundsAwaitingPayout(): Collection
    {
        return Refund::query()->whereIn('payment_id', Payment::query()->select('id'))
            ->whereIn('status', [RefundStatus::Requested->value, RefundStatus::PayingOut->value])->orderBy('id')->get();
    }

    public function paymentExists(int $paymentId): bool
    {
        return Payment::query()->whereKey($paymentId)->exists();
    }
}
