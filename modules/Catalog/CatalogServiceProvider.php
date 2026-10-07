<?php

declare(strict_types=1);

namespace Modules\Catalog;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Modules\Catalog\Application\Collections\EloquentCollectionDirectory;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Application\Products\EloquentVariantDirectory;
use Modules\Catalog\Application\Products\ProductImporter;
use Modules\Catalog\Application\Search\DatabaseSearchProvider;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Application\StorefrontCatalog;
use Modules\Catalog\Console\MediaCacheCommand;
use Modules\Catalog\Console\SearchReindexCommand;
use Modules\Catalog\Contracts\CatalogImporter;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Catalog\Contracts\ImageFormat;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Listeners\SyncProductSearchIndex;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
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
        $this->app->singleton(ImageCache::class, fn ($app): ImageCache => new ImageCache(
            ImageManager::usingDriver(extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class),
            $app->make(Extensions::class),
            rtrim((string) config('vanishop.media.cache.path'), '/'),
            array_map('intval', (array) config('vanishop.media.cache.widths')),
            max(1, min(100, (int) config('vanishop.media.cache.quality', 80))),
        ));

        $this->app->bind(CatalogReader::class, StorefrontCatalog::class);
        $this->app->bind(CatalogImporter::class, ProductImporter::class);
        $this->app->bind(VariantDirectory::class, EloquentVariantDirectory::class);
        $this->app->bind(CollectionDirectory::class, EloquentCollectionDirectory::class);
        $this->app->singleton(DatabaseSearchProvider::class);
        // Meilisearch/Algolia/Elasticsearch là plugin (vani.search-meilisearch…).
        $this->app->make(Extensions::class)->tag([DatabaseSearchProvider::class], SearchProvider::TAG);
        $this->app->make(Extensions::class)->requires(SearchProvider::TAG, Requirement::ExactlyOne, 'Tìm kiếm sản phẩm');
        $this->app->make(Extensions::class)->kindContract('search', SearchProvider::TAG);
        // Định dạng ảnh thu nhỏ (WebP/AVIF…) là plugin (vani.media-webp); không có → giữ định dạng gốc.
        $this->app->make(Extensions::class)->kindContract('image_format', ImageFormat::TAG);
        $this->app->bind(SearchManager::class, fn ($app): SearchManager => new SearchManager($app->make(Extensions::class), (string) config('vanishop.search.provider')));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        Event::listen([ProductCreated::class, ProductUpdated::class, ProductArchived::class], SyncProductSearchIndex::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SearchReindexCommand::class, MediaCacheCommand::class]);
        }

        // Không lưu tên class vào cột *_type (đổi namespace không làm hỏng dữ liệu).
        Relation::morphMap(['catalog.category' => Category::class, 'catalog.style_color' => StyleColor::class]);

        $permissions->register('catalog.view', 'Xem catalog');
        $permissions->register('catalog.manage', 'Sửa catalog (sản phẩm, thương hiệu, danh mục, thuộc tính, màu, size)');

        $navigation->add('catalog', 'Catalog', 'admin.catalog.home', 'catalog.view', 100);

        if (! $this->app->routesAreCached()) {
            Route::group([], $this->modulePath('Http/routes/media-cache.php'));
        }
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('catalog', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
