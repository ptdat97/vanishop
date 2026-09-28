<?php

declare(strict_types=1);

namespace Modules\Extension;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Shared\Support\AdminPath;
use ReflectionClass;

/**
 * Lớp cơ sở (public API) cho ServiceProvider của plugin.
 *
 * Mọi listener/menu đăng ký qua các helper ở đây gắn với plugin id, nên chỉ có hiệu lực
 * trong phạm vi (owner/brand/channel) mà plugin được bật.
 *
 * @see docs/05-plugin/plugin-system.md
 */
abstract class PluginServiceProvider extends ServiceProvider
{
    /**
     * Id plugin, trùng với "id" trong vanishop.json.
     */
    abstract protected function pluginId(): string;

    protected function pluginPath(string $path = ''): string
    {
        $directory = dirname((string) (new ReflectionClass(static::class))->getFileName());

        return $directory.($path !== '' ? '/'.$path : '');
    }

    protected function onFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->app->make(HookManager::class)->onFilter($hook, $callback, $priority, $this->pluginId());
    }

    protected function onAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->app->make(HookManager::class)->onAction($hook, $callback, $priority, $this->pluginId());
    }

    protected function onValidate(string $hook, callable $callback, int $priority = 10): void
    {
        $this->app->make(HookManager::class)->onValidate($hook, $callback, $priority, $this->pluginId());
    }

    protected function onSlot(string $hook, callable $callback, int $priority = 10): void
    {
        $this->app->make(HookManager::class)->onSlot($hook, $callback, $priority, $this->pluginId());
    }

    /**
     * Đóng góp implementation cho một extension point (vd. `vani.promotion.rules`).
     * Registry của Core chỉ thấy implementation này trong phạm vi plugin được bật.
     */
    protected function contribute(string $tag, string $implementation): void
    {
        $this->app->make(Extensions::class)->contribute($tag, $implementation, $this->pluginId());
    }

    protected function adminMenu(string $key, string $label, string $route, ?string $permission = null, int $order = 500): void
    {
        $this->app->make(AdminNavigation::class)->add($key, $label, $route, $permission, $order, $this->pluginId());
    }

    /**
     * @param  array<string, string>  $permissions  code => mô tả
     */
    protected function permissions(array $permissions): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        foreach ($permissions as $code => $description) {
            $registry->register($code, $description);
        }
    }

    /**
     * Route Admin của plugin: /{VANI_ADMIN_PATH}/plugins/{slug}/…, tên route admin.plugins.{slug}.…
     */
    protected function adminRoutes(string $file): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $slug = str_replace('.', '-', $this->pluginId());

        Route::middleware(['web', 'vani.admin', 'auth:staff', 'vani.staff-context', 'vani.plugin-active:'.$this->pluginId()])
            ->prefix(AdminPath::prefix()."/plugins/{$slug}")
            ->name("admin.plugins.{$slug}.")
            ->group($file);

        $this->refreshRouteLookups();
    }

    /**
     * Webhook từ dịch vụ ngoài: /api/integrations/{slug}/…
     */
    protected function webhookRoutes(string $file): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $slug = str_replace('.', '-', $this->pluginId());

        Route::middleware('api')->prefix("api/integrations/{$slug}")->name("integrations.{$slug}.")->group($file);

        $this->refreshRouteLookups();
    }

    /**
     * Route đăng ký sau khi app đã boot không nằm trong bảng tra cứu tên, khiến `Route::has()`/`route()`
     * không thấy (menu Admin biến mất). Nạp lại bảng tra cứu sau khi đăng ký route của plugin.
     */
    private function refreshRouteLookups(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    protected function migrations(string $path): void
    {
        $this->loadMigrationsFrom($path);
    }

    protected function translations(string $path, string $namespace): void
    {
        $this->loadTranslationsFrom($path, $namespace);
    }

    /**
     * Trang Inertia của plugin: Inertia::render('<Namespace>::<Trang>').
     */
    protected function adminPages(string $namespace, string $path): void
    {
        $this->callAfterResolving('inertia.view-finder', function (FileViewFinder $finder) use ($namespace, $path): void {
            $finder->addNamespace($namespace, $path);
        });
    }
}
