<?php

declare(strict_types=1);

namespace Modules\Ordering;

use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Ordering\Application\CustomerOrderService;
use Modules\Ordering\Application\EloquentOrderReader;
use Modules\Ordering\Application\EloquentOrderStatistics;
use Modules\Ordering\Application\OrderFactory;
use Modules\Ordering\Application\OrderTransitionService;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Shared\Support\ModuleServiceProvider;

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
        $this->app->bind(OrderReader::class, EloquentOrderReader::class);
        $this->app->bind(OrderStatistics::class, EloquentOrderStatistics::class);
        $this->app->bind(OrderTransitions::class, OrderTransitionService::class);
        $this->app->bind(CustomerOrders::class, CustomerOrderService::class);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('orders.view', 'Xem đơn hàng');
        $permissions->register('orders.manage', 'Xác nhận đơn, đổi địa chỉ, ghi chú');
        $permissions->register('orders.cancel', 'Huỷ đơn');

        $navigation->add('orders', 'Đơn hàng', 'admin.orders.home', 'orders.view', 50, group: 'sales');

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('orders', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
