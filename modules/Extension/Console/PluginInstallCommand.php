<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginInstallCommand extends Command
{
    protected $signature = 'vani:plugin:install {plugin : Id plugin, ví dụ vani.hello-world}';

    protected $description = 'Cài plugin: kiểm tra phụ thuộc và chạy migration';

    public function handle(PluginManager $plugins): int
    {
        try {
            $record = $plugins->install((string) $this->argument('plugin'));
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã cài {$record->id} {$record->version}.");

        return self::SUCCESS;
    }
}
