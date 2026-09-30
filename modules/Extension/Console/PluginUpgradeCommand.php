<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginUpgradeCommand extends Command
{
    protected $signature = 'vani:plugin:upgrade {plugin : Id plugin, ví dụ vani.vietqr}';

    protected $description = 'Nâng plugin đã cài lên version trong code: kiểm tra tương thích và chạy migration mới';

    public function handle(PluginManager $plugins): int
    {
        try {
            $record = $plugins->upgrade((string) $this->argument('plugin'));
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã nâng {$record->id} lên {$record->version} ({$record->status->value}).");

        return self::SUCCESS;
    }
}
