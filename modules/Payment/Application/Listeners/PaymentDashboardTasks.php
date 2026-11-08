<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Payment\Persistence\Models\Refund;

/**
 * Ô "Việc cần làm" của thanh toán (0.3.101): khoản hoàn chờ nhân viên chuyển trả (COD / chuyển khoản — cổng không tự hoàn).
 */
final class PaymentDashboardTasks
{
    /**
     * @return list<array{key: string, label: string, count: int, url: string, tone: string, order: int}>
     */
    public function __invoke(): array
    {
        if (! Gate::allows('payments.view')) {
            return [];
        }

        return [[
            'key' => 'payments.refunds_requested', 'label' => 'Hoàn tiền chờ chuyển trả', 'count' => Refund::query()->where('status', 'requested')->count(),
            'url' => route('admin.payment.payments.index'), 'tone' => 'warning', 'order' => 60,
        ]];
    }
}
