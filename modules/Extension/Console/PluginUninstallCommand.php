<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginUninstallCommand extends Command
{
    protected $signature = 'vani:plugin:uninstall {plugin} {--purge : Rollback migration và xoá bảng plg_* của plugin}';

    protected $description = 'Gỡ plugin (mặc định giữ dữ liệu)';

    public function handle(PluginManager $plugins): int
    {
        try {
            $plugins->uninstall((string) $this->argument('plugin'), (bool) $this->option('purge'));
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã gỡ '.$this->argument('plugin').'.');

        return self::SUCCESS;
    }
}
