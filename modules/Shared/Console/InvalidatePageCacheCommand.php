<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;

/**
 * Lô xoá cache trang (ADR-051 §4), scheduler chạy mỗi phút. `--all` xoá ngay toàn bộ cache trang.
 */
final class InvalidatePageCacheCommand extends Command
{
    protected $signature = 'vani:page-cache:invalidate {--all : Xoá ngay toàn bộ cache trang}';

    protected $description = 'Xoá cache trang theo các tag đang chờ (mỗi phút) hoặc toàn bộ';

    public function handle(BatchedPageCacheInvalidator $invalidator): int
    {
        if ($this->option('all')) {
            $invalidator->flush();
            $this->info('Đã xoá toàn bộ cache trang.');

            return self::SUCCESS;
        }

        $this->info('Đã xoá cache trang của '.$invalidator->process().' tag.');

        return self::SUCCESS;
    }
}
