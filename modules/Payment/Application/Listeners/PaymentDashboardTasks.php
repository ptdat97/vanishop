<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Payment\Application\ReconciliationLedger;
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

        $mismatches = app(ReconciliationLedger::class)->openCount();

        return [...($mismatches > 0 ? [[
            'key' => 'payments.reconciliation_mismatch', 'label' => 'Đối soát lệch', 'count' => $mismatches, 'hint' => 'Sao kê/bảng kê không khớp đơn',
            'url' => route('admin.payment.payments.index'), 'tone' => 'warning', 'order' => 61,
        ]] : []), [
            'key' => 'payments.refunds_requested', 'label' => 'Hoàn tiền chờ chuyển trả', 'count' => Refund::query()->whereIn('status', ['requested', 'paying_out'])->count(),
            'url' => route('admin.payment.payments.index'), 'tone' => 'warning', 'order' => 60,
        ]];
    }
}
