<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Integration\Application\ReplayService;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class ReplayCommand extends Command
{
    protected $signature = 'vani:integration:replay
        {--box=outbox : outbox | inbox}
        {--status= : failed | dead (mặc định cả hai)}
        {--target= : Target outbox (webhook:<id>, mã connector) hoặc system inbox}
        {--since= : Chỉ message tạo từ thời điểm này}
        {--id=* : Chỉ các id này}';

    protected $description = 'Phát lại message tích hợp failed/dead (giữ nguyên message id, có audit).';

    public function handle(ReplayService $replay, CurrentContext $context): int
    {
        $box = (string) $this->option('box');
        if (! in_array($box, ['outbox', 'inbox'], true)) {
            $this->error('--box phải là outbox hoặc inbox.');

            return self::INVALID;
        }

        $filter = array_filter([
            'ids' => $this->option('id') === [] ? null : array_map('intval', (array) $this->option('id')),
            'status' => $this->option('status') ?: null,
            'target' => $this->option('target') ?: null,
            'since' => $this->option('since') ? CarbonImmutable::parse((string) $this->option('since')) : null,
        ], fn (mixed $value): bool => $value !== null);

        $count = $context->runAs(ContextScope::system('cli integration replay'), fn (): int => $box === 'inbox' ? $replay->replayInbox($filter) : $replay->replayOutbox($filter));
        $this->info("Đã đưa {$count} message {$box} vào hàng đợi gửi lại.");

        return self::SUCCESS;
    }
}
