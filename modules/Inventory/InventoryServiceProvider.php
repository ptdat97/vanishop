<?php

declare(strict_types=1);

namespace Modules\Inventory;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Inventory\Application\ChannelAvailability;
use Modules\Inventory\Application\ReservationService;
use Modules\Inventory\Application\ReturnService;
use Modules\Inventory\Application\StandardInventoryStrategy;
use Modules\Inventory\Console\ReleaseExpiredReservationsCommand;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Shared\Support\ModuleServiceProvider;

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
        $this->app->singleton(StandardInventoryStrategy::class);
        $this->app->tag([StandardInventoryStrategy::class], ChannelAvailability::TAG);
        $this->app->bind(AvailabilityReader::class, fn ($app): ChannelAvailability => new ChannelAvailability(
            $app->make(VariantDirectory::class),
            $app,
            (string) config('vanishop.inventory.strategy', 'standard'),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('inventory.view', 'Xem tồn kho của brand');
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
        $this->loadBrandWorkspaceRoutes('inventory', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
