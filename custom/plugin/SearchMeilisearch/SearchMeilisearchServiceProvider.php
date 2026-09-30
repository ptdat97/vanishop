<?php

declare(strict_types=1);

namespace Plugin\SearchMeilisearch;

use Modules\Catalog\Contracts\SearchProvider;
use Modules\Extension\PluginServiceProvider;
use Plugin\SearchMeilisearch\Infrastructure\MeilisearchSearchProvider;

final class SearchMeilisearchServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.search-meilisearch';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/search-meilisearch.php'), 'vani.search-meilisearch');

        $this->app->singleton(MeilisearchSearchProvider::class, fn (): MeilisearchSearchProvider => new MeilisearchSearchProvider(
            (string) config('vani.search-meilisearch.host'),
            config('vani.search-meilisearch.key'),
            (string) config('vani.search-meilisearch.index'),
        ));
    }

    public function boot(): void
    {
        $this->contribute(SearchProvider::TAG, MeilisearchSearchProvider::class);
    }
}
