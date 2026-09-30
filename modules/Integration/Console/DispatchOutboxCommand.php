<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Illuminate\Console\Command;
use Modules\Integration\Application\OutboxWorker;

final class DispatchOutboxCommand extends Command
{
    protected $signature = 'vani:integration:dispatch
        {--limit=500 : Số message tối đa mỗi lượt}
        {--work : Chạy liên tục (supervisor) thay vì một lượt}
        {--max-time=3600 : Thời gian tối đa (giây) khi --work}
        {--sleep=2 : Giây nghỉ khi hàng đợi trống}';

    protected $description = 'Gửi message outbox tới webhook đối tác và connector (retry, backoff, dead letter).';

    public function handle(OutboxWorker $worker): int
    {
        return WorkLoop::run($this, fn (): int => $worker->run((int) $this->option('limit')), 'message outbox');
    }
}
