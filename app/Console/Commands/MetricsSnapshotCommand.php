<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Observability\HealthCheck;
use App\Observability\MetricsSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class MetricsSnapshotCommand extends Command
{
    protected $signature = 'vani:metrics:snapshot';

    protected $description = 'Chụp gauge vận hành (thanh toán chờ, outbox tồn, đối soát mở, plugin còn giao dịch dở) vào Pulse';

    public function handle(MetricsSnapshot $snapshot, CurrentContext $context): int
    {
        Cache::put(HealthCheck::HEARTBEAT_KEY, time(), 3600); // nhịp scheduler cho /health
        foreach ($context->runAs(ContextScope::system('vani:metrics:snapshot'), fn (): array => $snapshot->capture()) as $name => $value) {
            $this->line("{$name} = {$value}");
        }

        return self::SUCCESS;
    }
}
