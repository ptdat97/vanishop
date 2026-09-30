<?php

declare(strict_types=1);

namespace Modules\Extension;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginLoader;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginStateCache;
use Modules\Extension\Application\Plugins\ScopedExtensions;
use Modules\Extension\Console\PluginDisableCommand;
use Modules\Extension\Console\PluginEnableCommand;
use Modules\Extension\Console\PluginHooksCommand;
use Modules\Extension\Console\PluginInstallCommand;
use Modules\Extension\Console\PluginListCommand;
use Modules\Extension\Console\PluginUninstallCommand;
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
        ));
        $this->app->alias('eventy', Events::class);

        $this->app->scoped(PluginActivation::class);
        $this->app->singleton(ScopedExtensions::class, fn ($app): ScopedExtensions => new ScopedExtensions($app, fn (): PluginActivation => $app->make(PluginActivation::class)));
        $this->app->alias(ScopedExtensions::class, Extensions::class);
        $this->app->singleton(AdminNavigation::class, fn ($app): AdminNavigation => new AdminNavigation(fn (): PluginActivation => $app->make(PluginActivation::class)));
        $this->app->singleton(DependencyResolver::class);
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
            (string) config('vanishop.version'),
        ));

        // Nạp plugin đã cài: đặt ở register() để provider của plugin được boot cùng vòng với Core.
        $this->app->make(PluginLoader::class)->load();
    }

    public function boot(Router $router, PermissionRegistry $permissions): void
    {
        $router->aliasMiddleware('vani.plugin-active', EnsurePluginActive::class);

        $permissions->register('extension.plugins.view', 'Xem danh sách plugin');
        $permissions->register('extension.plugins.manage', 'Cài/bật/tắt plugin');
        $permissions->register('settings.manage', 'Sửa cấu hình theo phạm vi (Core và plugin)');

        $navigation = $this->app->make(AdminNavigation::class);
        $navigation->add('dashboard', 'Tổng quan', 'admin.dashboard', 'admin.access', 0);
        $navigation->add('plugins', 'Plugin', 'admin.plugins.index', 'extension.plugins.view', 900);
        $navigation->add('settings', 'Cấu hình', 'admin.settings.index', 'settings.manage', 950);

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
        $this->persistLoaderFailures();

        if ($this->app->runningInConsole()) {
            $this->commands([
                PluginListCommand::class,
                PluginInstallCommand::class,
                PluginEnableCommand::class,
                PluginDisableCommand::class,
                PluginUninstallCommand::class,
                PluginHooksCommand::class,
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
