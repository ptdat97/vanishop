<?php

declare(strict_types=1);

namespace Plugin\Warehouse;

use Modules\Extension\Contracts\Extensions;
use Modules\Extension\PluginServiceProvider;
use Modules\Inventory\Contracts\StockPageLink;
use Plugin\Warehouse\Application\TransferGuard;
use Plugin\Warehouse\Console\ReconcileCommand;
use Plugin\Warehouse\Infrastructure\LocationsLink;
use Plugin\Warehouse\Infrastructure\TransfersLink;

/**
 * Vận hành kho (ADR-042): nhiều kho/cửa hàng, chuyển kho, đối soát tồn với nguồn ngoài (ERP/POS). Tồn kho, giữ hàng và
 * sổ biến động ở Core (Inventory); plugin chỉ gọi contract LocationRegistry, StockMovements, InventoryReconciliation.
 * Cửa hàng một kho không cần plugin này.
 */
final class WarehouseServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.warehouse';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-warehouse');
        $this->permissions([
            'warehouse.locations.manage' => 'Quản lý kho/cửa hàng (thêm kho, ưu tiên, kho do ERP/POS quản lý)',
            'warehouse.transfer' => 'Tạo/điều phối chuyển kho',
        ]);

        $this->adminMenu('locations', 'Kho & cửa hàng', 'admin.plugins.vani-warehouse.locations.index', 'warehouse.locations.manage', 305, 'catalog');
        $this->adminMenu('transfers', 'Chuyển kho', 'admin.plugins.vani-warehouse.transfers.index', 'inventory.view', 310, 'catalog');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('Warehouse', $this->pluginPath('Resources/js/Pages'));
        // Nút trên trang Tồn kho của Core — tự ẩn khi plugin tắt.
        $this->contribute(StockPageLink::TAG, TransfersLink::class);
        $this->contribute(StockPageLink::TAG, LocationsLink::class);

        // Còn phiếu đang đi đường → không cho tắt plugin (hàng đã trừ kho đi, chưa nhận được ở kho đến).
        $this->contribute(TransferGuard::TAG, TransferGuard::class);
        $this->app->make(Extensions::class)->guardDisable(TransferGuard::TAG, fn (object $guard): ?string => $guard instanceof TransferGuard ? $guard->blocker() : null);

        if ($this->app->runningInConsole()) {
            $this->commands([ReconcileCommand::class]);
        }
    }
}
