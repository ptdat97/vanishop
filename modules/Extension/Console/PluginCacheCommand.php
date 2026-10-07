<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginStateCache;

/**
 * Dựng lại file trạng thái plugin (bootstrap/cache/vanishop-plugins.php) từ DB. Lúc boot plugin được nạp từ file này;
 * bản release mới (thư mục/container mới) chưa có file → không plugin nào được nạp. Chạy trong `php artisan optimize`.
 */
final class PluginCacheCommand extends Command
{
    protected $signature = 'vani:plugin:cache';

    protected $description = 'Dựng lại cache trạng thái plugin từ DB (chạy khi deploy, có trong php artisan optimize)';

    public function handle(PluginManager $plugins, PluginStateCache $cache): int
    {
        $plugins->rebuildCache();
        $ids = array_column($cache->read(), 'id');
        $this->info('Đã dựng cache plugin: '.($ids === [] ? 'không có plugin nào được nạp.' : implode(', ', $ids).'.'));

        return self::SUCCESS;
    }
}
