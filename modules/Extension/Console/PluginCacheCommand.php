<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginAssets;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginStateCache;

/**
 * Dựng lại file trạng thái plugin (bootstrap/cache/vanishop-plugins.php) từ DB. Lúc boot plugin được nạp từ file này;
 * bản release mới (thư mục/container mới) chưa có file → không plugin nào được nạp. Chạy trong `php artisan optimize`.
 * Đồng thời publish tài nguyên tĩnh (`assets`) của plugin được nạp sang public/plugins/ và xoá thư mục của plugin khác
 * (release mới chưa có public/plugins/).
 */
final class PluginCacheCommand extends Command
{
    protected $signature = 'vani:plugin:cache';

    protected $description = 'Dựng lại cache trạng thái plugin từ DB + publish tài nguyên tĩnh plugin (chạy khi deploy, có trong php artisan optimize)';

    public function handle(PluginManager $plugins, PluginStateCache $cache, ManifestRepository $manifests, PluginAssets $assets): int
    {
        $plugins->rebuildCache();
        $ids = array_column($cache->read(), 'id');
        $this->info('Đã dựng cache plugin: '.($ids === [] ? 'không có plugin nào được nạp.' : implode(', ', $ids).'.'));

        $published = 0;
        foreach ($ids as $id) {
            $manifest = $manifests->find((string) $id);
            $published += $manifest === null ? 0 : $assets->publish($manifest);
        }
        $assets->prune(array_map('strval', $ids));
        if ($published > 0) {
            $this->info("Đã publish {$published} tài nguyên tĩnh của plugin.");
        }

        return self::SUCCESS;
    }
}
