<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Integration\Application\OrderEventReconciler;

final class ReconcileOrdersCommand extends Command
{
    protected $signature = 'vani:integration:reconcile-orders
        {--hours=25 : Đối soát đơn thay đổi trong N giờ qua}
        {--grace=5 : Bỏ qua đơn thay đổi trong N phút gần nhất (listener có thể đang chạy)}
        {--dry-run : Chỉ báo cáo, không phát event bù}';

    protected $description = 'Đối soát đơn hàng ↔ event feed tích hợp; phát bù event bị thiếu.';

    public function handle(OrderEventReconciler $reconciler): int
    {
        $to = CarbonImmutable::now()->subMinutes((int) $this->option('grace'));
        $result = $reconciler->run($to->subHours((int) $this->option('hours')), $to, ! $this->option('dry-run'));

        $this->info("Đối soát #{$result['id']}: {$result['checked']} đơn, {$result['discrepancies']} thiếu, {$result['repaired']} đã bù.");

        return self::SUCCESS;
    }
}
