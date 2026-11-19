<?php

declare(strict_types=1);

namespace Modules\Returns\Console;

use Illuminate\Console\Command;
use Modules\Returns\Application\ReturnAttachmentPurge;
use Modules\Returns\Application\ReturnService;
use Modules\Tenancy\Contracts\Settings;

/**
 * Hằng đêm: xoá ảnh đổi trả của yêu cầu đã đóng quá hạn lưu (backlog L16).
 */
final class PurgeReturnAttachmentsCommand extends Command
{
    protected $signature = 'vani:returns:purge-attachments {--days= : Số ngày giữ ảnh sau khi yêu cầu đóng (mặc định theo cấu hình)}';

    protected $description = 'Xoá ảnh đổi trả của yêu cầu đã đóng quá hạn lưu';

    public function handle(ReturnAttachmentPurge $purge): int
    {
        $days = max(1, (int) ($this->option('days') ?? app(Settings::class)->get('core', ReturnService::ATTACHMENTS_RETENTION_DAYS)));
        $deleted = $purge->closedBefore(now()->subDays($days));
        $this->info("Đã xoá {$deleted} ảnh đổi trả (yêu cầu đóng quá {$days} ngày).");

        return self::SUCCESS;
    }
}
