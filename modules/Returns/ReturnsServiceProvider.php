<?php

declare(strict_types=1);

namespace Modules\Returns;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Customer\Events\CustomerAnonymized;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Returns\Application\DaysWindowPolicy;
use Modules\Returns\Application\EloquentReturnStatistics;
use Modules\Returns\Application\Listeners\OrderReturnsPanel;
use Modules\Returns\Application\Listeners\ReturnDashboardTasks;
use Modules\Returns\Application\ReturnedVariants;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Console\PurgeReturnAttachmentsCommand;
use Modules\Returns\Contracts\ReturnPolicy;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Contracts\ReturnStatistics;
use Modules\Returns\Listeners\PurgeAnonymizedCustomerAttachments;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;

final class ReturnsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Returns';
    }

    public function register(): void
    {
        // Màn hình Admin của module mở cho plugin thêm cột/lọc/thao tác/công cụ (EP v3 X1).
        $this->app->make(AdminScreen::class)->declareResource('return');
        $this->app->bind(Returns::class, ReturnService::class);
        $this->app->bind(ReturnStatistics::class, EloquentReturnStatistics::class);
        $this->app->bind(DaysWindowPolicy::class, fn (): DaysWindowPolicy => new DaysWindowPolicy((int) config('vanishop.fulfillment.return_window_days', 7)));
        $this->app->make(Extensions::class)->tag([DaysWindowPolicy::class], ReturnPolicy::TAG);
        $this->app->make(Extensions::class)->tag([ReturnedVariants::class], VariantUsage::TAG);
        $this->app->make(Extensions::class)->requires(ReturnPolicy::TAG, Requirement::ExactlyOne, 'Chính sách đổi trả');
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        Hook::onSlot('vani.admin.dashboard.tasks', fn (): array => $this->app->make(ReturnDashboardTasks::class)());
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'returns.policy', 'Chính sách đổi trả', 'select', (string) config('vanishop.returns.policy', 'days_window'),
            optionsFromTag: ReturnPolicy::TAG, help: 'ReturnPolicy áp cho đơn của cửa hàng.',
        ));
        $permissions->register('returns.view', 'Xem yêu cầu đổi/trả');
        $permissions->register('returns.manage', 'Duyệt, từ chối, nhận hàng trả');
        $permissions->register('returns.refund', 'Hoàn tất đổi/trả và hoàn tiền');

        $navigation->add('returns', 'Đổi/trả', 'admin.returns.home', 'returns.view', 70, group: 'sales');
        Hook::onSlot('vani.admin.order.sidebar', fn (OrderDetail $order) => $this->app->make(OrderReturnsPanel::class)($order), priority: 110);

        // Ảnh đổi trả là dữ liệu cá nhân (L16): xoá khi yêu cầu đóng quá hạn lưu, và ngay khi khách bị ẩn danh hoá.
        Event::listen(CustomerAnonymized::class, PurgeAnonymizedCustomerAttachments::class);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:returns:purge-attachments')->dailyAt('03:45')->withoutOverlapping()->onOneServer();
        });
        if ($this->app->runningInConsole()) {
            $this->commands([PurgeReturnAttachmentsCommand::class]);
        }

        $this->loadWebRoutes($this->modulePath('Http/routes/attachments.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('returns', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
