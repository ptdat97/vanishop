<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\Persistence\Models\NotificationLog;
use Modules\Notification\Persistence\Models\NotificationTemplate;
use Modules\Shared\Http\ListSort;

/**
 * Truy vấn đọc cho màn hình Admin của Notification — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /**
     * @return Collection<int, NotificationTemplate>
     */
    public function templates(): Collection
    {
        return NotificationTemplate::query()->orderBy('type')->orderBy('channel')->orderBy('locale')->get();
    }

    /**
     * Nhật ký gửi: lọc trạng thái, tìm theo người nhận hoặc khoá idempotency.
     *
     * @return LengthAwarePaginator<int, NotificationLog>
     */
    public function logs(string $status, string $search, int $perPage, ?ListSort $sort = null): LengthAwarePaginator
    {
        return NotificationLog::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('recipient', 'like', "%{$search}%")->orWhere('idempotency_key', 'like', "%{$search}%")))
            ->when($sort !== null, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('id'))
            ->paginate($perPage)->withQueryString();
    }
}
