<?php

declare(strict_types=1);

namespace Modules\Ordering;

use Modules\Catalog\Contracts\VariantUsage;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Ordering\Application\CustomerDeletionGuard;
use Modules\Ordering\Application\CustomerOrderService;
use Modules\Ordering\Application\EloquentOrderReader;
use Modules\Ordering\Application\EloquentOrderStatistics;
use Modules\Ordering\Application\Listeners\OrderDashboardTasks;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Application\OrderedVariants;
use Modules\Ordering\Application\OrderFactory;
use Modules\Ordering\Application\OrderReceipt;
use Modules\Ordering\Application\OrderRiskService;
use Modules\Ordering\Application\OrderTransitionService;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\OrderActions;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderRisks;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

/**
 * Slice 6: tạo đơn (snapshot, số đơn, order_events). Slice 7: state machine + OrderTransitions (Payment cần).
 * Slice 8: Admin đơn, tra cứu/huỷ cho khách (CustomerOrders), đổi địa chỉ, ghi chú.
 */
final class OrderingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Ordering';
    }

    public function register(): void
    {
        // Ma trận biến thể: SKU đã có ở Ordering chỉ ngừng bán, không xoá (variant-matrix §3.2).
        $this->app->make(Extensions::class)->tag([OrderedVariants::class], VariantUsage::TAG);
        $this->app->bind(OrderWriter::class, OrderFactory::class);
        // Màn hình Admin của module mở cho plugin thêm form/cột/lọc/thao tác/tab (ADR-030).
        $this->app->make(AdminScreen::class)->declareResource('order');
        $this->app->bind(OrderReader::class, EloquentOrderReader::class);
        $this->app->bind(OrderStatistics::class, EloquentOrderStatistics::class);
        $this->app->bind(OrderTransitions::class, OrderTransitionService::class);
        // EP v3 X2: lệnh của nhân viên cho plugin tự động hoá; tín hiệu rủi ro đã gộp.
        $this->app->bind(OrderActions::class, OrderCommands::class);
        $this->app->bind(OrderRisks::class, OrderRiskService::class);
        $this->app->bind(CustomerOrders::class, CustomerOrderService::class);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        Hook::onSlot('vani.admin.dashboard.tasks', fn (): array => $this->app->make(OrderDashboardTasks::class)());
        $permissions->register('orders.view', 'ordering::texts.ordering_service_provider.view_orders');
        $permissions->register('orders.manage', 'ordering::texts.ordering_service_provider.confirm_orders_change_addresses_notes');
        $permissions->register('orders.cancel', 'ordering::texts.ordering_service_provider.cancel_order');
        $permissions->register('orders.override_status', 'ordering::texts.ordering_service_provider.change_order_status_out_sequence');

        $navigation->group('sales', 'ordering::texts.ordering_service_provider.sales', 45);
        $navigation->add('orders', 'ordering::texts.ordering_service_provider.orders', 'admin.orders.home', 'orders.view', 50, group: 'sales');

        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', OrderReceipt::SETTING_COMPLETE, 'ordering::texts.ordering_service_provider.complete_order_when_customer_confirms', 'bool', false,
            help: 'ordering::texts.ordering_service_provider.customer_clicks_i_ve_received',
            group: SettingGroup::Orders,
        ));

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('orders', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();

        Hook::onValidate('vani.customer.before_delete', fn (int $customerId): array => $this->app->make(CustomerDeletionGuard::class)($customerId));
    }
}
