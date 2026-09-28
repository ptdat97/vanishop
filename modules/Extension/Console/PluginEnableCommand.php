<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginEnableCommand extends Command
{
    protected $signature = 'vani:plugin:enable {plugin} {--scope=owner : owner | brand:<id> | channel:<id>}';

    protected $description = 'Bật plugin trong một phạm vi';

    public function handle(PluginManager $plugins): int
    {
        try {
            [$type, $id] = ScopeOption::parse((string) $this->option('scope'));
            $plugins->enable((string) $this->argument('plugin'), $type, $id);
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã bật '.$this->argument('plugin').' ở scope '.$this->option('scope').'.');

        return self::SUCCESS;
    }
}
