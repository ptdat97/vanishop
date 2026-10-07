<?php

declare(strict_types=1);

namespace Plugin\DemoCatalog;

use Modules\Extension\PluginServiceProvider;
use Plugin\DemoCatalog\Console\SeedDemoCatalogCommand;

/**
 * Dữ liệu demo: catalog có ảnh chụp thật (VaniCommerce/public/image/catalog/products). Cũng là implementation tham
 * chiếu cho CatalogImporter / PriceImporter / StockImporter (R26).
 */
final class DemoCatalogServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.demo-catalog';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/demo-catalog.php'), 'vani.demo-catalog');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SeedDemoCatalogCommand::class]);
        }
    }
}
