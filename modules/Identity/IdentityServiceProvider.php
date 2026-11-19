<?php

declare(strict_types=1);

namespace Modules\Identity;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Modules\Identity\Application\DatabaseAuditLogger;
use Modules\Identity\Application\EloquentStaffNames;
use Modules\Identity\Application\InMemoryPermissionRegistry;
use Modules\Identity\Application\RbacAuthorizer;
use Modules\Identity\Console\CreateStaffCommand;
use Modules\Identity\Console\ResetStaffPasswordCommand;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Identity\Contracts\StaffNames;
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
        $this->app->singleton(PermissionRegistry::class, InMemoryPermissionRegistry::class);
        $this->app->scoped(Authorizer::class, RbacAuthorizer::class);
        $this->app->scoped(AuditLogger::class, DatabaseAuditLogger::class);
        $this->app->bind(StaffNames::class, EloquentStaffNames::class);
        // Chụp khi config đã nạp và trước mọi request; dùng để trả phiên về mặc định cho request storefront.
        $this->app->singleton(ConfigureAdminSession::DEFAULTS_BINDING, fn (): array => ConfigureAdminSession::snapshotDefaults());
    }

    public function boot(Router $router, PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(ConfigureAdminSession::DEFAULTS_BINDING);

        $router->aliasMiddleware('vani.staff-context', ResolveStaffContext::class);

        // Chính sách mật khẩu nhân viên (không dùng 2FA → mật khẩu mạnh là bắt buộc, ADR-020).
        Password::defaults(fn (): Password => Password::min(12)
            ->when((bool) config('vanishop.admin.check_breached_passwords'), fn (Password $rule): Password => $rule->uncompromised()));

        $permissions->register('admin.access', 'identity::texts.identity_service_provider.access_admin_area');
        $permissions->register('staff.manage', 'identity::texts.identity_service_provider.manage_staff_roles');
        $navigation->add('staff', 'identity::texts.identity_service_provider.staff', 'admin.staff.index', 'staff.manage', 810, group: 'system');

        // Permission đã khai báo trong registry được quyết định bởi RBAC của Identity.
        Gate::before(function (mixed $user, string $ability) use ($permissions): ?bool {
            if (! $user instanceof StaffUser || ! $permissions->has($ability)) {
                return null;
            }

            return $this->app->make(Authorizer::class)->allows($user->id, $ability);
        });

        $this->loadWebRoutes($this->modulePath('Http/routes/admin-auth.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-profile.php'));
        $this->loadAdminSectionRoutes('staff', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();

        if ($this->app->runningInConsole()) {
            $this->commands([CreateStaffCommand::class, ResetStaffPasswordCommand::class]);
        }
    }
}
