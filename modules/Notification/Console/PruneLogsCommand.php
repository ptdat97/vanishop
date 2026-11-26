<?php

declare(strict_types=1);

namespace Modules\Notification\Console;

use Illuminate\Console\Command;
use Modules\Shared\Support\ChunkedPrune;
use Modules\Tenancy\Contracts\Settings;

/**
 * Xoá nhật ký gửi tin cũ hơn `notification.retention_months` (mặc định 6, db-refactor G3). Chạy hằng đêm; chạy lại an toàn.
 */
final class PruneLogsCommand extends Command
{
    public const SETTING = 'notification.retention_months';

    protected $signature = 'vani:notification:prune';

    protected $description = 'Xoá nhật ký gửi tin cũ hơn số tháng cấu hình';

    public function handle(Settings $settings): int
    {
        $before = now()->subMonths(max(1, (int) $settings->get('core', self::SETTING, 6)));
        $deleted = ChunkedPrune::olderThan('notification_logs', 'created_at', $before);
        $this->info("Đã xoá {$deleted} nhật ký gửi tin cũ hơn {$before->toDateString()}.");

        return self::SUCCESS;
    }
}
