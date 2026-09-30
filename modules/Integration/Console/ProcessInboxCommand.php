<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Illuminate\Console\Command;
use Modules\Integration\Application\InboxProcessor;

final class ProcessInboxCommand extends Command
{
    protected $signature = 'vani:integration:process-inbox
        {--limit=500 : Số message tối đa mỗi lượt}
        {--work : Chạy liên tục (supervisor) thay vì một lượt}
        {--max-time=3600 : Thời gian tối đa (giây) khi --work}
        {--sleep=2 : Giây nghỉ khi hàng đợi trống}';

    protected $description = 'Xử lý message inbox bằng InboundHandler của plugin.';

    public function handle(InboxProcessor $processor): int
    {
        return WorkLoop::run($this, fn (): int => $processor->run((int) $this->option('limit')), 'message inbox');
    }
}
