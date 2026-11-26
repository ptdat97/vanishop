<?php

declare(strict_types=1);

namespace Modules\Identity;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Modules\Identity\Application\AdminPathSetting;
use Modules\Identity\Application\DatabaseAuditLogger;
use Modules\Identity\Application\EloquentStaffNames;
use Modules\Identity\Application\InMemoryPermissionRegistry;
use Modules\Identity\Application\RbacAuthorizer;
use Modules\Identity\Console\AdminPathCommand;
use Modules\Identity\Console\ArchiveAuditCommand;
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
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    /** Gate chỉ super admin qua được (`Authorizer::isSuperAdmin`). */
    public const SUPER_ADMIN = 'identity.super_admin';

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
        // Sau khi mọi provider đã đăng ký (cache, DB sẵn sàng), trước khi provider nào boot (route, Horizon, Pulse).
        $this->app->booting(fn () => $this->applyAdminPath());
    }

    /**
     * Đường dẫn Admin lưu trong DB (super admin đổi ở Admin) → `vanishop.admin.path`, trước khi route Admin và dashboard
     * Horizon/Pulse (dưới `/{admin}/system/…`) được dựng. Đặt `HORIZON_PATH`/`PULSE_PATH` thì giữ giá trị đó.
     */
    private function applyAdminPath(): void
    {
        $path = $this->app->make(AdminPathSetting::class)->current();
        config([
            'vanishop.admin.path' => $path,
            'horizon.path' => config('horizon.path') ?? "{$path}/system/horizon",
            'pulse.path' => config('pulse.path') ?? "{$path}/system/pulse",
        ]);
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
        $navigation->add('admin-path', 'identity::texts.admin_path.nav', 'admin.security.admin-path.edit', self::SUPER_ADMIN, 830, group: 'system');

        // Permission đã khai báo trong registry được quyết định bởi RBAC của Identity.
        Gate::before(function (mixed $user, string $ability) use ($permissions): ?bool {
            if (! $user instanceof StaffUser || ! $permissions->has($ability)) {
                return null;
            }

            return $this->app->make(Authorizer::class)->allows($user->id, $ability);
        });
        // Chỉ super admin (vai trò toàn quyền `*`): không phải permission đăng ký nên không cấp được qua vai trò.
        Gate::define(self::SUPER_ADMIN, fn (mixed $user): bool => $user instanceof StaffUser && $this->app->make(Authorizer::class)->isSuperAdmin($user->id));

        $this->loadWebRoutes($this->modulePath('Http/routes/admin-auth.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-profile.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-security.php'));
        $this->loadAdminSectionRoutes('staff', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();

        if ($this->app->runningInConsole()) {
            $this->commands([CreateStaffCommand::class, ResetStaffPasswordCommand::class, AdminPathCommand::class, ArchiveAuditCommand::class]);
        }
        // Lưu trữ nhật ký thao tác (db-refactor G3).
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', ArchiveAuditCommand::SETTING, 'identity::texts.identity_service_provider.audit_archive_months', 'int', 24,
            help: 'identity::texts.identity_service_provider.audit_archive_months_help', rules: ['min:6', 'max:240'], group: SettingGroup::Other,
        ));
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:audit:archive')->dailyAt('04:30')->withoutOverlapping()->onOneServer();
        });
    }
}
