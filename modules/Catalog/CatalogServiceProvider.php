<?php

declare(strict_types=1);

namespace Modules\Catalog;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Application\Products\EloquentVariantDirectory;
use Modules\Catalog\Application\Search\DatabaseSearchProvider;
use Modules\Catalog\Application\Search\MeilisearchSearchProvider;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Application\StorefrontCatalog;
use Modules\Catalog\Console\SearchReindexCommand;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Listeners\SyncProductSearchIndex;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Shared\Support\ModuleServiceProvider;

final class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Catalog';
    }

    public function register(): void
    {
        $this->app->bind(MediaLibrary::class, fn (): MediaLibrary => new MediaLibrary((string) config('vanishop.media.disk', 'public')));

        $this->app->bind(CatalogReader::class, StorefrontCatalog::class);
        $this->app->bind(VariantDirectory::class, EloquentVariantDirectory::class);
        $this->app->singleton(DatabaseSearchProvider::class);
        $this->app->singleton(MeilisearchSearchProvider::class, fn (): MeilisearchSearchProvider => new MeilisearchSearchProvider(
            (string) config('vanishop.search.meilisearch.host'),
            config('vanishop.search.meilisearch.key'),
            (string) config('vanishop.search.meilisearch.index'),
        ));
        $this->app->tag([DatabaseSearchProvider::class, MeilisearchSearchProvider::class], SearchManager::TAG);
        $this->app->bind(SearchManager::class, fn ($app): SearchManager => new SearchManager($app, (string) config('vanishop.search.provider')));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        Event::listen([ProductCreated::class, ProductUpdated::class, ProductArchived::class], SyncProductSearchIndex::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SearchReindexCommand::class]);
        }

        // Không lưu tên class vào cột *_type (đổi namespace không làm hỏng dữ liệu).
        Relation::morphMap(['catalog.category' => Category::class, 'catalog.style_color' => StyleColor::class]);

        $permissions->register('catalog.view', 'Xem catalog của brand');
        $permissions->register('catalog.manage', 'Sửa catalog của brand (danh mục, thuộc tính, màu, size)');

        $navigation->add('catalog', 'Catalog', 'admin.catalog.home', 'catalog.view', 100);

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadBrandWorkspaceRoutes('catalog', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
