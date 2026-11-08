<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnRequest;

/**
 * Ô "Việc cần làm" của đổi/trả (0.3.101): yêu cầu chờ duyệt; hàng trả đã nhận chờ hoàn tất (hoàn tiền / đổi hàng).
 */
final class ReturnDashboardTasks
{
    /**
     * @return list<array{key: string, label: string, count: int, url: string, tone: string, order: int}>
     */
    public function __invoke(): array
    {
        if (! Gate::allows('returns.view')) {
            return [];
        }
        $count = fn (ReturnStatus $status): int => ReturnRequest::query()->where('status', $status)->count();
        $url = fn (ReturnStatus $status): string => route('admin.returns.returns.index', ['status' => $status->value]);

        return [
            ['key' => 'returns.requested', 'label' => 'Đổi/trả chờ duyệt', 'count' => $count(ReturnStatus::Requested), 'url' => $url(ReturnStatus::Requested), 'tone' => 'warning', 'order' => 40],
            ['key' => 'returns.received', 'label' => 'Hàng trả đã nhận, chờ hoàn tất', 'count' => $count(ReturnStatus::Received), 'url' => $url(ReturnStatus::Received), 'tone' => 'info', 'order' => 45],
        ];
    }
}
