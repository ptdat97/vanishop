<?php

declare(strict_types=1);

namespace Modules\Inventory;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Inventory\Application\AuthoritySyncService;
use Modules\Inventory\Application\ReservationService;
use Modules\Inventory\Application\ReturnService;
use Modules\Inventory\Application\StandardInventoryStrategy;
use Modules\Inventory\Application\StockAvailability;
use Modules\Inventory\Console\ReleaseExpiredReservationsCommand;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Inventory\Contracts\InventoryStrategy;
use Modules\Inventory\Contracts\InventorySync;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;

final class InventoryServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Inventory';
    }

    public function register(): void
    {
        $this->app->bind(InventoryReservation::class, ReservationService::class);
        $this->app->bind(InventoryReturns::class, ReturnService::class);
        $this->app->bind(InventorySync::class, AuthoritySyncService::class);
        $this->app->singleton(StandardInventoryStrategy::class);
        $this->app->make(Extensions::class)->tag([StandardInventoryStrategy::class], InventoryStrategy::TAG);
        $this->app->make(Extensions::class)->requires(InventoryStrategy::TAG, Requirement::ExactlyOne, 'Chiến lược tồn bán được');
        $this->app->bind(AvailabilityReader::class, fn ($app): StockAvailability => new StockAvailability(
            $app->make(Extensions::class),
            $app->make(Settings::class),
            (string) config('vanishop.inventory.strategy', 'standard'),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'inventory.strategy', 'Cách tính số bán được (ATS)', 'select', (string) config('vanishop.inventory.strategy', 'standard'),
            optionsFromTag: InventoryStrategy::TAG, help: 'Strategy chỉ giảm được ATS so với công thức chuẩn.',
        ));
        $permissions->register('inventory.view', 'Xem tồn kho');
        $permissions->register('inventory.adjust', 'Điều chỉnh/kiểm kê tồn kho');
        $permissions->register('inventory.locations.manage', 'Quản lý kho/cửa hàng (cấp Owner)');

        $navigation->add('inventory', 'Tồn kho', 'admin.inventory.home', 'inventory.view', 300);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:inventory:release-expired')->everyMinute()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([ReleaseExpiredReservationsCommand::class]);
        }

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-owner.php'));
        $this->loadAdminSectionRoutes('inventory', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
