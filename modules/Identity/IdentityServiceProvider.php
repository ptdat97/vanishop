<?php

declare(strict_types=1);

namespace Modules\Identity;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Modules\Identity\Application\DatabaseAuditLogger;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Identity\Application\ScopedRbacAuthorizer;
use Modules\Identity\Console\CreateStaffCommand;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Identity\Http\Middleware\ConfigureAdminSession;
use Modules\Identity\Http\Middleware\ResolveStaffContext;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Support\ModuleServiceProvider;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Identity';
    }

    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
        $this->app->scoped(Authorizer::class, ScopedRbacAuthorizer::class);
        $this->app->scoped(AuditLogger::class, DatabaseAuditLogger::class);
        // Chụp khi config đã nạp và trước mọi request; dùng để trả phiên về mặc định cho request storefront.
        $this->app->singleton(ConfigureAdminSession::DEFAULTS_BINDING, fn (): array => ConfigureAdminSession::snapshotDefaults());
    }

    public function boot(Router $router, PermissionRegistry $permissions): void
    {
        $this->app->make(ConfigureAdminSession::DEFAULTS_BINDING);

        $router->aliasMiddleware('vani.staff-context', ResolveStaffContext::class);

        // Chính sách mật khẩu nhân viên (không dùng 2FA → mật khẩu mạnh là bắt buộc, ADR-020).
        Password::defaults(fn (): Password => Password::min(12)
            ->when((bool) config('vanishop.admin.check_breached_passwords'), fn (Password $rule): Password => $rule->uncompromised()));

        $permissions->register('admin.access', 'Truy cập trang quản trị');
        $permissions->register('staff.manage', 'Quản lý nhân viên và vai trò');

        // Permission đã khai báo trong registry được quyết định bởi RBAC theo phạm vi.
        // Tham số đầu tiên có thể là ScopeRef để kiểm tra theo location.
        Gate::before(function (mixed $user, string $ability, array $arguments) use ($permissions): ?bool {
            if (! $user instanceof StaffUser || ! $permissions->has($ability)) {
                return null;
            }

            $target = ($arguments[0] ?? null) instanceof ScopeRef ? $arguments[0] : null;

            return $this->app->make(Authorizer::class)->allows($user->id, $ability, $target);
        });

        $this->loadWebRoutes($this->modulePath('Http/routes/admin-auth.php'));
        $this->bootModuleResources();

        if ($this->app->runningInConsole()) {
            $this->commands([CreateStaffCommand::class]);
        }
    }
}
