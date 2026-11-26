<?php

declare(strict_types=1);

namespace Modules\Inventory;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Catalog\Events\ProductListingProjected;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Inventory\Application\AuthoritySyncService;
use Modules\Inventory\Application\BackorderService;
use Modules\Inventory\Application\EloquentLocationRegistry;
use Modules\Inventory\Application\IncomingStockService;
use Modules\Inventory\Application\InventoryReconciler;
use Modules\Inventory\Application\Listeners\ProductListStock;
use Modules\Inventory\Application\Listeners\StockDashboardTasks;
use Modules\Inventory\Application\Listeners\SyncListingAvailability;
use Modules\Inventory\Application\LowStockThreshold;
use Modules\Inventory\Application\OnlineLocations;
use Modules\Inventory\Application\ReservationService;
use Modules\Inventory\Application\ReturnService;
use Modules\Inventory\Application\StandardInventoryStrategy;
use Modules\Inventory\Application\StockAvailability;
use Modules\Inventory\Application\StockCountImporter;
use Modules\Inventory\Application\StockedVariants;
use Modules\Inventory\Application\StockLedger;
use Modules\Inventory\Application\StockMovementService;
use Modules\Inventory\Application\StockQueries;
use Modules\Inventory\Application\VariantStockFields;
use Modules\Inventory\Console\EnsureDefaultLocationCommand;
use Modules\Inventory\Console\ReleaseExpiredReservationsCommand;
use Modules\Inventory\Console\VerifyStockCommand;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\Backorders;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Inventory\Contracts\InventoryReconciliation;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Inventory\Contracts\InventoryStrategy;
use Modules\Inventory\Contracts\InventorySync;
use Modules\Inventory\Contracts\LocationRegistry;
use Modules\Inventory\Contracts\StockImporter;
use Modules\Inventory\Contracts\StockJournal;
use Modules\Inventory\Contracts\StockMovements;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

final class InventoryServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Inventory';
    }

    public function register(): void
    {
        // Màn hình Admin của module mở cho plugin thêm cột/lọc/thao tác/công cụ (EP v3 X1).
        $this->app->make(AdminScreen::class)->declareResource('stock');
        $this->app->bind(InventoryReservation::class, ReservationService::class);
        $this->app->bind(InventoryReturns::class, ReturnService::class);
        $this->app->bind(InventorySync::class, AuthoritySyncService::class);
        $this->app->bind(StockImporter::class, StockCountImporter::class);
        // Contract cho vận hành kho (plugin vani.warehouse, ADR-042): kho, biến động chuyển kho, đối soát nguồn ngoài.
        $this->app->bind(LocationRegistry::class, EloquentLocationRegistry::class);
        $this->app->bind(StockMovements::class, StockMovementService::class);
        $this->app->bind(StockJournal::class, StockQueries::class);
        $this->app->bind(InventoryReconciliation::class, InventoryReconciler::class);
        $this->app->singleton(StandardInventoryStrategy::class);
        $this->app->make(Extensions::class)->tag([StandardInventoryStrategy::class], InventoryStrategy::TAG);
        // Ma trận biến thể (variant-matrix §3.3): cột tồn (cửa hàng một kho) + SKU đã có tồn/biến động là đã phát sinh.
        $this->app->make(Extensions::class)->tag([VariantStockFields::class], VariantFieldProvider::TAG);
        $this->app->make(Extensions::class)->tag([StockedVariants::class], VariantUsage::TAG);
        $this->app->make(Extensions::class)->requires(InventoryStrategy::TAG, Requirement::ExactlyOne, 'inventory::texts.inventory_service_provider.sellable_stock_strategy');
        $this->app->bind(AvailabilityReader::class, fn ($app): StockAvailability => new StockAvailability(
            $app->make(Extensions::class),
            $app->make(Settings::class),
            (string) config('vanishop.inventory.strategy', 'standard'),
            $app->make(VariantDirectory::class),
            $app->make(OnlineLocations::class),
            $app->make(BackorderService::class),
        ));
        // Đặt trước có giới hạn và hàng đang về (ADR-047, EP v3 X7).
        $this->app->bind(Backorders::class, BackorderService::class);
        $this->app->bind(IncomingStock::class, IncomingStockService::class);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        // Read model trang danh sách của Catalog: "còn hàng" (db-refactor §4.3).
        Event::listen([AvailabilityChanged::class, ProductListingProjected::class], SyncListingAvailability::class);
        Hook::onSlot('vani.admin.dashboard.tasks', fn (): array => $this->app->make(StockDashboardTasks::class)());
        Hook::onSlot('vani.admin.product.list_columns', fn (array $productIds): ?array => $this->app->make(ProductListStock::class)->column($productIds));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'inventory.strategy', 'inventory::texts.inventory_service_provider.sellable_quantity_calculation_ats', 'select', (string) config('vanishop.inventory.strategy', 'standard'),
            optionsFromTag: InventoryStrategy::TAG, help: 'inventory::texts.inventory_service_provider.strategy_only_reduce_ats_compared',
            group: SettingGroup::Inventory,
        ));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', LowStockThreshold::SETTING, 'inventory::texts.inventory_service_provider.low_stock_threshold', 'int', (int) config('vanishop.inventory.low_stock_threshold', 3),
            help: 'inventory::texts.inventory_service_provider.below_sellable_quantity_admin_highlights',
            rules: ['min:0', 'max:1000'],
            group: SettingGroup::Inventory,
        ));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', StockLedger::DEFAULT_SAFETY_STOCK, 'inventory::texts.inventory_service_provider.default_safety_stock', 'int', 0,
            help: 'inventory::texts.inventory_service_provider.default_safety_stock_help',
            rules: ['min:0', 'max:1000'],
            group: SettingGroup::Inventory,
        ));
        $permissions->register('inventory.view', 'inventory::texts.inventory_service_provider.view_inventory', [PermissionRegistry::PRESET_ORDER_OPS, PermissionRegistry::PRESET_ANALYST]);
        $permissions->register('inventory.adjust', 'inventory::texts.inventory_service_provider.adjust_count_inventory', [PermissionRegistry::PRESET_ORDER_OPS]);
        $permissions->register('inventory.locations.manage', 'inventory::texts.inventory_service_provider.edit_main_warehouse_details_create');

        $navigation->add('inventory', 'inventory::texts.inventory_service_provider.inventory', 'admin.inventory.home', 'inventory.view', 140, group: 'catalog');

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
