<?php

declare(strict_types=1);

namespace Modules\Extension;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Inertia\ResponseFactory;
use Modules\Extension\Application\Admin\AdminExtensions;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Application\Admin\Reports;
use Modules\Extension\Application\Hooks\CallerPlugin;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Hooks\Points\HookedInertiaFactory;
use Modules\Extension\Application\Hooks\Points\ResponseHooks;
use Modules\Extension\Application\Hooks\Points\ViewHooks;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginCapabilities;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginHealth;
use Modules\Extension\Application\Plugins\PluginLoader;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginStateCache;
use Modules\Extension\Application\Plugins\RequiredExtensions;
use Modules\Extension\Application\Plugins\ScopedExtensions;
use Modules\Extension\Application\Storefront\AccountPages;
use Modules\Extension\Application\Storefront\PluginViews;
use Modules\Extension\Application\Storefront\StorefrontPrefixes;
use Modules\Extension\Console\InstallCommand;
use Modules\Extension\Console\PluginCacheCommand;
use Modules\Extension\Console\PluginDisableCommand;
use Modules\Extension\Console\PluginDoctorCommand;
use Modules\Extension\Console\PluginEnableCommand;
use Modules\Extension\Console\PluginFinishDrainingCommand;
use Modules\Extension\Console\PluginHealthCommand;
use Modules\Extension\Console\PluginHooksCommand;
use Modules\Extension\Console\PluginInstallCommand;
use Modules\Extension\Console\PluginListCommand;
use Modules\Extension\Console\PluginUninstallCommand;
use Modules\Extension\Console\PluginUpgradeCommand;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Domain\Plugin\DependencyResolver;
use Modules\Extension\Http\Middleware\EnsurePluginActive;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\ModuleServiceProvider;
use Throwable;
use TorMorten\Eventy\Events;

final class ExtensionServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Extension';
    }

    public function register(): void
    {
        $this->app->singleton(HookRegistry::class, function (): HookRegistry {
            $registry = new HookRegistry;

            /** @var list<string> $modules */
            $modules = config('modules', []);
            foreach ($modules as $module) {
                $file = base_path("modules/{$module}/hooks.php");
                if (is_file($file)) {
                    $registry->declareMany(require $file);
                }
            }

            return $registry;
        });

        $this->app->singleton(HookManager::class, fn ($app): HookManager => new HookManager(
            $app->make(Events::class),
            $app->make(HookRegistry::class),
            fn (): PluginActivation => $app->make(PluginActivation::class),
            (bool) config('vanishop.hooks.strict'),
            (float) config('vanishop.hooks.slow_ms', 50),
        ));
        $this->app->alias('eventy', Events::class);

        $this->app->scoped(PluginActivation::class);
        $this->app->singleton(ScopedExtensions::class, fn ($app): ScopedExtensions => new ScopedExtensions($app, fn (): PluginActivation => $app->make(PluginActivation::class)));
        $this->app->alias(ScopedExtensions::class, Extensions::class);
        $this->app->singleton(PluginViews::class);
        $this->app->singleton(StorefrontPrefixes::class);
        $this->app->singleton(AccountPages::class, fn ($app): AccountPages => new AccountPages(fn (): PluginActivation => $app->make(PluginActivation::class)));
        $this->app->singleton(CallerPlugin::class);
        // Điểm mở rộng tự động cho mọi trang Admin (ADR-031).
        $this->app->singleton(ResponseFactory::class, HookedInertiaFactory::class);
        $this->app->singleton(AdminExtensions::class, fn ($app): AdminExtensions => new AdminExtensions(fn (): PluginActivation => $app->make(PluginActivation::class)));
        $this->app->alias(AdminExtensions::class, AdminScreen::class);
        $this->app->singleton(AdminNavigation::class, fn ($app): AdminNavigation => new AdminNavigation(fn (): PluginActivation => $app->make(PluginActivation::class)));
        $this->app->singleton(DependencyResolver::class);
        $this->app->bind(PluginDoctor::class, fn ($app): PluginDoctor => new PluginDoctor(
            $app->make(ManifestRepository::class), $app->make(DependencyResolver::class), $app->make(PluginLoader::class), $app->make(RequiredExtensions::class), $app->make(Extensions::class), $app->make(PluginHealth::class), (string) config('vanishop.version'), $app->make(PluginCapabilities::class),
        ));
        $this->app->singleton(ManifestRepository::class, fn (): ManifestRepository => new ManifestRepository((string) config('vanishop.plugins.path')));
        $this->app->singleton(PluginStateCache::class, fn ($app): PluginStateCache => new PluginStateCache($app->make(Filesystem::class), (string) config('vanishop.plugins.cache')));
        $this->app->singleton(PluginLoader::class, fn ($app): PluginLoader => new PluginLoader($app, $app->make(PluginStateCache::class), (bool) config('vanishop.plugins.safe_mode')));
        $this->app->bind(PluginManager::class, fn ($app): PluginManager => new PluginManager(
            $app->make(ManifestRepository::class),
            $app->make(DependencyResolver::class),
            $app->make(PluginStateCache::class),
            $app->make(PluginActivation::class),
            $app->make(AuditLogger::class),
            $app->make(ConsoleKernel::class),
            $app->make(RequiredExtensions::class),
            $app->make(PluginCapabilities::class),
            (string) config('vanishop.version'),
        ));

        // Nạp plugin đã cài: đặt ở register() để provider của plugin được boot cùng vòng với Core.
        $this->app->make(PluginLoader::class)->load();
    }

    public function boot(Router $router, PermissionRegistry $permissions): void
    {
        $router->aliasMiddleware('vani.plugin-active', EnsurePluginActive::class);
        $router->aliasMiddleware('vani.response-hooks', ResponseHooks::class);
        View::composer('theme::*', ViewHooks::class);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:plugin:health')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
            $schedule->command('vani:plugin:finish-draining')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        });

        $permissions->register('extension.plugins.view', 'Xem danh sách plugin');
        $permissions->register('extension.plugins.manage', 'Cài/bật/tắt plugin');
        $permissions->register('settings.manage', 'Sửa cấu hình theo phạm vi (Core và plugin)');

        $navigation = $this->app->make(AdminNavigation::class);
        $navigation->group('sales', 'Bán hàng', 45);
        $navigation->group('catalog', 'Sản phẩm', 95);
        $navigation->group('marketing', 'Marketing', 240);
        $navigation->group('content', 'Nội dung & giao diện', 590);
        $navigation->group('system', 'Hệ thống', 890);
        $navigation->add('dashboard', 'Tổng quan', 'admin.dashboard', 'admin.access', 0);
        $navigation->add('reports', 'Báo cáo', 'admin.reports.index', 'admin.access', 40, when: fn (): bool => $this->app->make(Reports::class)->visible() !== []);
        $navigation->add('plugins', 'Plugin', 'admin.plugins.index', 'extension.plugins.view', 900, group: 'system');
        $navigation->add('settings', 'Cấu hình', 'admin.settings.index', 'settings.manage', 950, group: 'system');

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
        $this->persistLoaderFailures();

        if ($this->app->runningInConsole()) {
            // Không gắn vào optimize:clear: xoá file cache = tắt mọi plugin ở lần boot sau.
            $this->optimizes(optimize: 'vani:plugin:cache', key: 'vanishop-plugins');
            $this->commands([
                InstallCommand::class,
                PluginCacheCommand::class,
                PluginHealthCommand::class,
                PluginListCommand::class,
                PluginInstallCommand::class,
                PluginEnableCommand::class,
                PluginDisableCommand::class,
                PluginFinishDrainingCommand::class,
                PluginUninstallCommand::class,
                PluginHooksCommand::class,
                PluginUpgradeCommand::class,
                PluginDoctorCommand::class,
            ]);
        }
    }

    /**
     * Plugin nạp lỗi được đánh dấu failed để lần sau không nạp lại (tự cô lập).
     */
    private function persistLoaderFailures(): void
    {
        $failures = $this->app->make(PluginLoader::class)->failures();
        if ($failures === []) {
            return;
        }

        try {
            if (Schema::hasTable('plugins')) {
                $manager = $this->app->make(PluginManager::class);
                foreach ($failures as $pluginId => $error) {
                    $manager->markFailed($pluginId, $error);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Không ghi được trạng thái failed của plugin.', ['exception' => $exception]);
        }
    }
}
