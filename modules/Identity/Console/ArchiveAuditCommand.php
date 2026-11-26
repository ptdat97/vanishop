<?php

declare(strict_types=1);

namespace Modules\Identity\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Support\ChunkedPrune;
use Modules\Tenancy\Contracts\Settings;

/**
 * Chuyển nhật ký thao tác cũ hơn `identity.audit_archive_months` (mặc định 24) sang `audit_logs_archive` theo lô
 * (db-refactor G3) — không xoá dữ liệu, chỉ giữ bảng đang ghi nhỏ. Chạy hằng đêm; chạy lại an toàn.
 */
final class ArchiveAuditCommand extends Command
{
    public const SETTING = 'identity.audit_archive_months';

    protected $signature = 'vani:audit:archive';

    protected $description = 'Chuyển nhật ký thao tác cũ sang bảng lưu trữ';

    public function handle(Settings $settings): int
    {
        $before = now()->subMonths(max(1, (int) $settings->get('core', self::SETTING, 24)));
        $columns = ['id', 'actor_type', 'actor_id', 'action', 'subject_type', 'subject_id', 'changes', 'ip_address', 'user_agent', 'correlation_id', 'created_at'];
        $moved = ChunkedPrune::olderThan('audit_logs', 'created_at', $before, beforeDelete: function (array $ids) use ($columns): void {
            DB::table('audit_logs_archive')->insertUsing($columns, DB::table('audit_logs')->whereIn('id', $ids)->select($columns));
        });
        $this->info("Đã chuyển {$moved} nhật ký thao tác cũ hơn {$before->toDateString()} sang audit_logs_archive.");

        return self::SUCCESS;
    }
}
