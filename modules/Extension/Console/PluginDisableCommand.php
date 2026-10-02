<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginDisableCommand extends Command
{
    protected $signature = 'vani:plugin:disable {plugin}';

    protected $description = 'Tắt plugin';

    public function handle(PluginManager $plugins): int
    {
        try {
            $plugins->disable((string) $this->argument('plugin'));
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã tắt '.$this->argument('plugin').'.');

        return self::SUCCESS;
    }
}
