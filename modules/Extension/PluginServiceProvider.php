<?php

declare(strict_types=1);

namespace Modules\Extension;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;
use Modules\Extension\Application\Admin\AdminExtensions;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginEventListeners;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Shared\Support\AdminPath;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;
use ReflectionClass;

/**
 * Lớp cơ sở (public API) cho ServiceProvider của plugin.
 *
 * Mọi listener/menu đăng ký qua các helper ở đây gắn với plugin id, nên chỉ có hiệu lực
 * khi plugin đang bật.
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
     * Nghe domain event (`Modules\<Ctx>\Events\*`) — chỉ chạy khi plugin đang bật; lỗi được ghi log, không làm
     * hỏng flow. Không dùng `Event::listen()` trực tiếp: listener khi đó chạy cả khi plugin đã tắt.
     *
     * @param  class-string  $event
     * @param  callable|class-string|array{0: class-string, 1: string}  $handler  class-string → `handle($event)`
     */
    protected function onEvent(string $event, callable|string|array $handler): void
    {
        $this->app->make(PluginEventListeners::class)->listen($event, $handler, $this->pluginId());
    }

    /**
     * Tác vụ định kỳ của plugin: chỉ chạy khi plugin đang bật. Tác vụ chạy không có
     * CurrentContext — cần thì tự đặt (vd. `CurrentContext::runAs(ContextScope::system('…'), …)`).
     *
     *   $this->schedule(fn (Schedule $schedule) => $schedule->command('vani:einvoice:sync')->everyFiveMinutes());
     *
     * @param  Closure(Schedule): mixed  $define
     */
    protected function schedule(Closure $define): void
    {
        $pluginId = $this->pluginId();
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) use ($define, $pluginId): void {
            $before = count($schedule->events());
            $define($schedule);
            foreach (array_slice($schedule->events(), $before) as $event) {
                $event->when(fn (): bool => $this->app->make(PluginActivation::class)->isActive($pluginId));
            }
        });
    }

    /**
     * Khai báo cấu hình của plugin (namespace = plugin id) → Admin → Cấu hình sinh form.
     * Đọc lúc chạy qua contract `Modules\Tenancy\Contracts\Settings::get($pluginId, $key, $default)`.
     *
     * @param  list<array{key: string, label: string, type?: string, default?: mixed, options?: array<string, string>, help?: string}>  $definitions
     */
    protected function settings(array $definitions): void
    {
        $settings = $this->app->make(Settings::class);
        foreach ($definitions as $definition) {
            $settings->define(new SettingDefinition(
                $this->pluginId(), $definition['key'], $definition['label'], $definition['type'] ?? 'string', $definition['default'] ?? null,
                $definition['options'] ?? [], $definition['help'] ?? null,
            ));
        }
    }

    /**
     * Đóng góp implementation cho một extension point (vd. `vani.promotion.rules`).
     * Registry của Core chỉ thấy implementation này trong phạm vi plugin được bật.
     */
    protected function contribute(string $tag, string $implementation): void
    {
        $this->app->make(Extensions::class)->contribute($tag, $implementation, $this->pluginId());
    }

    /**
     * Phần form của plugin trên màn hình tạo/sửa tài nguyên của Core (ADR-030 §4.A). `save` chạy trong transaction
     * lưu của Core, sau Core; lỗi → rollback cả form.
     *
     * @param  list<FieldDefinition>  $fields
     * @param  Closure(int): array<string, mixed>  $load  id → giá trị hiện tại
     * @param  Closure(int, array<string, mixed>): void  $save
     */
    protected function adminFormSection(string $resource, string $key, string $label, array $fields, Closure $load, Closure $save, int $order = 500): void
    {
        $this->app->make(AdminExtensions::class)->section($resource, $this->pluginId(), $key, $label, $fields, $load, $save, $order);
    }

    /**
     * Cột trên trang danh sách. `resolve` nhận cả trang id (batch) và trả id => giá trị.
     *
     * @param  Closure(list<int>): array<int, scalar|null>  $resolve
     */
    protected function adminColumn(string $resource, string $key, string $label, Closure $resolve, int $order = 500): void
    {
        $this->app->make(AdminExtensions::class)->column($resource, $this->pluginId(), $key, $label, $resolve, $order);
    }

    /**
     * Nút thao tác trên trang chi tiết (`detail`) hoặc chọn nhiều ở danh sách (`bulk`). Core kiểm tra quyền + audit.
     *
     * @param  Closure(int): (string|null)  $handle
     * @param  'detail'|'bulk'|'both'  $scope
     */
    protected function adminAction(string $resource, string $key, string $label, string $permission, Closure $handle, string $scope = 'detail', bool $confirm = false, int $order = 500): void
    {
        $this->app->make(AdminExtensions::class)->action($resource, $this->pluginId(), $key, $label, $permission, $handle, $scope, $confirm, $order);
    }

    /**
     * Tab trên trang chi tiết: danh sách dòng nhãn/giá trị. UI phức tạp hơn → trang riêng của plugin (`adminPages`).
     *
     * @param  Closure(int): list<array{label: string, value: string}>  $rows
     */
    protected function adminTab(string $resource, string $key, string $label, Closure $rows, int $order = 500): void
    {
        $this->app->make(AdminExtensions::class)->tab($resource, $this->pluginId(), $key, $label, $rows, $order);
    }

    /**
     * Bộ lọc trên trang danh sách: `apply` nhận giá trị đã chọn và trả id thoả (plugin không chạm truy vấn của Core).
     *
     * @param  array<string, string>|Closure(): array<string, string>  $options
     * @param  Closure(string): list<int>  $apply
     */
    protected function adminFilter(string $resource, string $key, string $label, array|Closure $options, Closure $apply, int $order = 500): void
    {
        $this->app->make(AdminExtensions::class)->filter($resource, $this->pluginId(), $key, $label, $options, $apply, $order);
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
