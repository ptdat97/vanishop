<?php

declare(strict_types=1);

namespace Modules\Ordering;

use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Ordering\Application\CustomerDeletionGuard;
use Modules\Ordering\Application\CustomerOrderService;
use Modules\Ordering\Application\EloquentOrderReader;
use Modules\Ordering\Application\EloquentOrderStatistics;
use Modules\Ordering\Application\Listeners\OrderDashboardTasks;
use Modules\Ordering\Application\OrderFactory;
use Modules\Ordering\Application\OrderReceipt;
use Modules\Ordering\Application\OrderTransitionService;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
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
        $this->app->bind(OrderWriter::class, OrderFactory::class);
        // Màn hình Admin của module mở cho plugin thêm form/cột/lọc/thao tác/tab (ADR-030).
        $this->app->make(AdminScreen::class)->declareResource('order');
        $this->app->bind(OrderReader::class, EloquentOrderReader::class);
        $this->app->bind(OrderStatistics::class, EloquentOrderStatistics::class);
        $this->app->bind(OrderTransitions::class, OrderTransitionService::class);
        $this->app->bind(CustomerOrders::class, CustomerOrderService::class);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        Hook::onSlot('vani.admin.dashboard.tasks', fn (): array => $this->app->make(OrderDashboardTasks::class)());
        $permissions->register('orders.view', 'Xem đơn hàng');
        $permissions->register('orders.manage', 'Xác nhận đơn, đổi địa chỉ, ghi chú');
        $permissions->register('orders.cancel', 'Huỷ đơn');
        $permissions->register('orders.override_status', 'Đổi trạng thái đơn ngoài trình tự (bắt buộc ghi lý do)');

        $navigation->group('sales', 'Bán hàng', 45);
        $navigation->add('orders', 'Đơn hàng', 'admin.orders.home', 'orders.view', 50, group: 'sales');

        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', OrderReceipt::SETTING_COMPLETE, 'Hoàn tất đơn khi khách xác nhận đã nhận hàng', 'bool', false,
            help: 'Bật: khách bấm "Đã nhận hàng" + chọn không đổi trả (hoặc đã quá hạn đổi trả) → đơn hoàn tất ngay thay vì chờ hết hạn đổi trả. Tắt: chỉ ghi nhận.',
        ));

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('orders', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();

        Hook::onValidate('vani.customer.before_delete', fn (int $customerId): array => $this->app->make(CustomerDeletionGuard::class)($customerId));
    }
}
