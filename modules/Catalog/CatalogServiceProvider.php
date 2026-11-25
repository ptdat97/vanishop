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
use Modules\Catalog\Application\Collections\EloquentCollectionPages;
use Modules\Catalog\Application\EloquentCatalogLookup;
use Modules\Catalog\Application\Media\EloquentMediaDirectory;
use Modules\Catalog\Application\Media\EloquentMediaUrls;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Application\Media\ImageSizes;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Application\Products\EloquentVariantDirectory;
use Modules\Catalog\Application\Products\ProductIdentifiers;
use Modules\Catalog\Application\Products\ProductImporter;
use Modules\Catalog\Application\Products\ProductViewCounter;
use Modules\Catalog\Application\Products\StyleListPrices;
use Modules\Catalog\Application\StorefrontCatalog;
use Modules\Catalog\Console\MediaCacheCommand;
use Modules\Catalog\Contracts\CatalogImporter;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Catalog\Contracts\Data\DefaultWeight;
use Modules\Catalog\Contracts\ImageFormat;
use Modules\Catalog\Contracts\ListPrices;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\MediaUrls;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Contracts\VideoDriver;
use Modules\Catalog\Listeners\DeclareSettingMedia;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;
use Modules\Tenancy\Events\SettingChanged;

final class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Catalog';
    }

    public function register(): void
    {
        $this->app->bind(MediaLibrary::class, fn ($app): MediaLibrary => new MediaLibrary((string) config('vanishop.media.disk', 'public'), $app->make(ImageCache::class)));
        $this->app->singleton(ImageSizes::class);
        $this->app->singleton(ImageCache::class, fn ($app): ImageCache => new ImageCache(
            ImageManager::usingDriver(extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class),
            $app->make(Extensions::class),
            $app->make(ImageSizes::class),
            rtrim((string) config('vanishop.media.cache.path'), '/'),
        ));

        $this->app->bind(CatalogReader::class, StorefrontCatalog::class);
        // Màn hình Admin của module mở cho plugin thêm form/cột/lọc/thao tác/tab (ADR-030).
        $this->app->make(AdminScreen::class)->declareResource('product');
        $this->app->make(AdminScreen::class)->declareResource('category');
        $this->app->make(AdminScreen::class)->declareResource('brand');
        $this->app->make(AdminScreen::class)->declareResource('collection');
        // Bộ lọc phụ của SkuPicker (EP v3 X1): plugin lọc SKU theo dữ liệu riêng (bộ sưu tập, nhãn…); id = variant id.
        $this->app->make(AdminScreen::class)->declareResource('variant');
        $this->app->bind(MediaDirectory::class, EloquentMediaDirectory::class);
        // Scoped: ghi nhớ ảnh đã tra trong một request (theme gọi vani_image() nhiều lần).
        $this->app->scoped(MediaUrls::class, EloquentMediaUrls::class);
        $this->app->bind(ProductViewStatistics::class, ProductViewCounter::class);
        $this->app->bind(CatalogImporter::class, ProductImporter::class);
        $this->app->bind(VariantDirectory::class, EloquentVariantDirectory::class);
        $this->app->bind(ListPrices::class, StyleListPrices::class);
        $this->app->bind(CollectionDirectory::class, EloquentCollectionDirectory::class);
        $this->app->bind(CollectionPages::class, EloquentCollectionPages::class);
        $this->app->bind(CatalogLookup::class, EloquentCatalogLookup::class);
        // Định dạng ảnh thu nhỏ (WebP/AVIF…) là plugin (vani.media-webp); không có → giữ định dạng gốc.
        $this->app->make(Extensions::class)->kindContract('image_format', ImageFormat::TAG);
        // Nền tảng video sản phẩm (YouTube…) là plugin (vani.video-youtube); không có → không thêm được video.
        $this->app->make(Extensions::class)->kindContract('video', VideoDriver::TAG);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        // Ảnh chọn ở cấu hình kiểu `media` là "đang dùng" trong Thư viện ảnh (backlog L22).
        Event::listen(SettingChanged::class, DeclareSettingMedia::class);

        if ($this->app->runningInConsole()) {
            $this->commands([MediaCacheCommand::class]);
        }

        // Không lưu tên class vào cột *_type (đổi namespace không làm hỏng dữ liệu).
        Relation::morphMap(['catalog.category' => Category::class, 'catalog.variant' => Variant::class]);

        $permissions->register('catalog.view', 'Xem catalog', [PermissionRegistry::PRESET_ORDER_OPS, PermissionRegistry::PRESET_MARKETING, PermissionRegistry::PRESET_CONTENT, PermissionRegistry::PRESET_ANALYST]);
        $permissions->register('catalog.manage', 'catalog::texts.catalog_service_provider.edit_catalogue_products_brands_categories', [PermissionRegistry::PRESET_CONTENT]);

        $permissions->register('media.view', 'catalog::texts.catalog_service_provider.view_choose_images_from_media', [PermissionRegistry::PRESET_MARKETING, PermissionRegistry::PRESET_CONTENT]);
        $permissions->register('media.manage', 'catalog::texts.catalog_service_provider.manage_media_library_upload_folders', [PermissionRegistry::PRESET_CONTENT]);

        $navigation->group('catalog', 'catalog::texts.catalog_service_provider.products', 95);
        $navigation->add('catalog', 'Catalog', 'admin.catalog.home', 'catalog.view', 100, group: 'catalog');
        $navigation->add('media', 'catalog::texts.catalog_service_provider.media_library', 'admin.media.index', 'media.view', 610, group: 'content');

        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', EloquentMediaUrls::PLACEHOLDER_SETTING, 'catalog::texts.catalog_service_provider.placeholder_image', 'media',
            help: 'catalog::texts.catalog_service_provider.shown_products_without_images_product',
            rules: ['min:1', 'exists:media,id'],
            group: SettingGroup::Appearance,
        ));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', DefaultWeight::SETTING, 'catalog::texts.catalog_service_provider.default_weight_gram', 'int', (int) config('vanishop.fulfillment.default_item_weight_gram', 300),
            help: 'catalog::texts.catalog_service_provider.default_weight_gram_help', rules: ['min:1', 'max:50000'], group: SettingGroup::Inventory,
        ));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', ProductIdentifiers::PREFIX_SETTING, 'catalog::texts.catalog_service_provider.style_code_prefix', 'string',
            help: 'catalog::texts.catalog_service_provider.style_code_prefix_help', rules: ['max:20', 'regex:/^[A-Za-z0-9._-]*$/'], group: SettingGroup::Store,
        ));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', ProductIdentifiers::DIGITS_SETTING, 'catalog::texts.catalog_service_provider.style_code_digits', 'int', 4,
            help: 'catalog::texts.catalog_service_provider.style_code_digits_help', rules: ['min:1', 'max:10'], group: SettingGroup::Store,
        ));

        if (! $this->app->routesAreCached()) {
            Route::group([], $this->modulePath('Http/routes/media-cache.php'));
        }
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('catalog', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->loadAdminSectionRoutes('media', $this->modulePath('Http/routes/admin-media.php'));
        $this->bootModuleResources();
    }
}
