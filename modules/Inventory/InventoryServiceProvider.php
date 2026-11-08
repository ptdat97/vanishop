<?php

declare(strict_types=1);

namespace Modules\Inventory;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Identity\Application\ScopeOptions;
use Modules\Identity\Domain\ScopeType;
use Modules\Inventory\Application\AuthoritySyncService;
use Modules\Inventory\Application\EloquentLocationRegistry;
use Modules\Inventory\Application\InventoryReconciler;
use Modules\Inventory\Application\Listeners\StockDashboardTasks;
use Modules\Inventory\Application\ReservationService;
use Modules\Inventory\Application\ReturnService;
use Modules\Inventory\Application\StandardInventoryStrategy;
use Modules\Inventory\Application\StockAvailability;
use Modules\Inventory\Application\StockCountImporter;
use Modules\Inventory\Application\StockMovementService;
use Modules\Inventory\Console\EnsureDefaultLocationCommand;
use Modules\Inventory\Console\ReleaseExpiredReservationsCommand;
use Modules\Inventory\Console\VerifyStockCommand;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\InventoryReconciliation;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Inventory\Contracts\InventoryStrategy;
use Modules\Inventory\Contracts\InventorySync;
use Modules\Inventory\Contracts\LocationRegistry;
use Modules\Inventory\Contracts\StockImporter;
use Modules\Inventory\Contracts\StockMovements;
use Modules\Inventory\Persistence\Models\Location;
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
        $this->app->bind(StockImporter::class, StockCountImporter::class);
        // Contract cho vận hành kho (plugin vani.warehouse, ADR-042): kho, biến động chuyển kho, đối soát nguồn ngoài.
        $this->app->bind(LocationRegistry::class, EloquentLocationRegistry::class);
        $this->app->bind(StockMovements::class, StockMovementService::class);
        $this->app->bind(InventoryReconciliation::class, InventoryReconciler::class);
        $this->app->singleton(StandardInventoryStrategy::class);
        $this->app->make(Extensions::class)->tag([StandardInventoryStrategy::class], InventoryStrategy::TAG);
        $this->app->make(Extensions::class)->requires(InventoryStrategy::TAG, Requirement::ExactlyOne, 'Chiến lược tồn bán được');
        $this->app->bind(AvailabilityReader::class, fn ($app): StockAvailability => new StockAvailability(
            $app->make(Extensions::class),
            $app->make(Settings::class),
            (string) config('vanishop.inventory.strategy', 'standard'),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation, ScopeOptions $scopes): void
    {
        Hook::onSlot('vani.admin.dashboard.tasks', fn (): array => $this->app->make(StockDashboardTasks::class)());
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'inventory.strategy', 'Cách tính số bán được (ATS)', 'select', (string) config('vanishop.inventory.strategy', 'standard'),
            optionsFromTag: InventoryStrategy::TAG, help: 'Strategy chỉ giảm được ATS so với công thức chuẩn.',
        ));
        $permissions->register('inventory.view', 'Xem tồn kho');
        $permissions->register('inventory.adjust', 'Điều chỉnh/kiểm kê tồn kho');
        $permissions->register('inventory.locations.manage', 'Sửa thông tin Kho chính, tạo Kho chính (cấp Owner)');

        $navigation->add('inventory', 'Tồn kho', 'admin.inventory.home', 'inventory.view', 300, group: 'catalog');
        // Admin → Nhân viên: gán vai trò theo phạm vi một location.
        $scopes->register(ScopeType::Location, fn (): array => Location::query()->orderBy('code')->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (Location $location): array => [$location->id => "{$location->code} — {$location->name}"])->all());

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:inventory:release-expired')->everyMinute()->withoutOverlapping()->onOneServer();
            $schedule->command('vani:inventory:verify')->dailyAt('03:30')->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([EnsureDefaultLocationCommand::class, ReleaseExpiredReservationsCommand::class, VerifyStockCommand::class]);
        }

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-owner.php'));
        $this->loadAdminSectionRoutes('inventory', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
