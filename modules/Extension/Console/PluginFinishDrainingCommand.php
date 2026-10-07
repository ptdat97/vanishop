<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Tắt các plugin đang ngừng (draining) đã xử lý xong việc dở dang. Chạy định kỳ (5 phút).
 */
final class PluginFinishDrainingCommand extends Command
{
    protected $signature = 'vani:plugin:finish-draining';

    protected $description = 'Tắt plugin draining đã hết giao dịch dở dang';

    public function handle(PluginManager $plugins, CurrentContext $context): int
    {
        $finished = $context->runAs(ContextScope::system('vani:plugin:finish-draining'), fn (): array => $plugins->finishDraining());
        $this->info($finished === [] ? 'Không có plugin nào hoàn tất ngừng.' : 'Đã tắt: '.implode(', ', $finished).'.');

        return self::SUCCESS;
    }
}
