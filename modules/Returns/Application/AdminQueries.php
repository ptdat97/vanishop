<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Http\ListSort;

/**
 * Truy vấn đọc cho màn hình Admin của Returns — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /**
     * Danh sách yêu cầu đổi trả: lọc trạng thái, tìm theo số yêu cầu hoặc số đơn, id do bộ lọc plugin trả.
     *
     * @param  list<int>|null  $ids
     * @return LengthAwarePaginator<int, ReturnRequest>
     */
    public function returns(string $status, string $search, ?int $orderId, ?array $ids, int $perPage, ?ListSort $sort = null): LengthAwarePaginator
    {
        return ReturnRequest::query()->withCount('lines')->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match->where('number', $search)->orWhere('order_id', $orderId ?? 0)))
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($sort !== null, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('id'))->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, int> trạng thái => số yêu cầu (thẻ lọc)
     */
    public function countsByStatus(): array
    {
        return ReturnRequest::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($total): int => (int) $total)->all();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, ReturnRequest>
     */
    public function returnsByIds(array $ids, bool $withLines = false): Collection
    {
        return ReturnRequest::query()->when($withLines, fn ($query) => $query->with('lines'))->whereIn('id', $ids)->get();
    }
}
