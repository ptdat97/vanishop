<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;

/**
 * Lớp cơ sở cho ServiceProvider của module Core: nạp migration, route,
 * view, bản dịch và trang Inertia theo quy ước thư mục của module.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Tên module, trùng với tên thư mục trong modules/.
     */
    abstract protected function moduleName(): string;

    protected function modulePath(string $path = ''): string
    {
        return base_path('modules/'.$this->moduleName().($path !== '' ? '/'.$path : ''));
    }

    /**
     * Nạp các tài nguyên có trong module (bỏ qua thư mục không tồn tại).
     */
    protected function bootModuleResources(): void
    {
        $migrations = $this->modulePath('Persistence/Database/migrations');
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        $namespace = strtolower($this->moduleName());

        $views = $this->modulePath('resources/views');
        if (is_dir($views)) {
            $this->loadViewsFrom($views, $namespace);
        }

        $lang = $this->modulePath('resources/lang');
        if (is_dir($lang)) {
            $this->loadTranslationsFrom($lang, $namespace);
        }

        $pages = $this->modulePath('resources/js/Pages');
        if (is_dir($pages)) {
            $this->registerInertiaPages($this->moduleName(), $pages);
        }
    }

    /**
     * Route Admin dưới đường dẫn cấu hình được (VANI_ADMIN_PATH). Middleware do module Identity định nghĩa.
     */
    protected function loadAdminRoutes(string $file): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware(['web', 'vani.admin', 'auth:staff', 'vani.staff-context'])
                ->prefix(AdminPath::prefix())
                ->name('admin.')
                ->group($file);
        }
    }

    /**
     * Storefront API v1: /api/storefront/v1/… (khách vãng lai, locale theo X-Vani-Locale).
     */
    protected function loadStorefrontApiRoutes(string $file): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware(['api', 'throttle:storefront-api', 'vani.storefront-context'])
                ->prefix('api/storefront/v1')
                ->name('api.storefront.v1.')
                ->group($file);
        }
    }

    /**
     * Admin theo khu vực: /{admin}/{section}/…, tên route admin.{section}.…
     */
    protected function loadAdminSectionRoutes(string $section, string $file): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware(['web', 'vani.admin', 'auth:staff', 'vani.staff-context'])
                ->prefix(AdminPath::prefix()."/{$section}")
                ->name("admin.{$section}.")
                ->group($file);
        }
    }

    protected function loadWebRoutes(string $file): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group($file);
        }
    }

    /**
     * Cho phép Inertia::render('<Namespace>::<Trang>') tìm thấy file trang của module/plugin.
     */
    protected function registerInertiaPages(string $namespace, string $path): void
    {
        $this->callAfterResolving('inertia.view-finder', function (FileViewFinder $finder) use ($namespace, $path): void {
            $finder->addNamespace($namespace, $path);
        });
    }
}
