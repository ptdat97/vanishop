<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginEnableCommand extends Command
{
    protected $signature = 'vani:plugin:enable {plugin}';

    protected $description = 'Bật plugin cho cửa hàng';

    public function handle(PluginManager $plugins): int
    {
        try {
            $plugins->enable((string) $this->argument('plugin'));
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã bật '.$this->argument('plugin').'.');

        return self::SUCCESS;
    }
}
