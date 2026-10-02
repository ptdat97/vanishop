<?php

declare(strict_types=1);

namespace Modules\Returns;

use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Returns\Application\DaysWindowPolicy;
use Modules\Returns\Application\Listeners\OrderReturnsPanel;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Contracts\ReturnPolicy;
use Modules\Returns\Contracts\Returns;
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
        $this->app->bind(Returns::class, ReturnService::class);
        $this->app->bind(DaysWindowPolicy::class, fn (): DaysWindowPolicy => new DaysWindowPolicy((int) config('vanishop.fulfillment.return_window_days', 7)));
        $this->app->make(Extensions::class)->tag([DaysWindowPolicy::class], ReturnPolicy::TAG);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'returns.policy', 'Chính sách đổi trả', 'select', (string) config('vanishop.returns.policy', 'days_window'),
            optionsFromTag: ReturnPolicy::TAG, help: 'ReturnPolicy áp cho đơn của cửa hàng.',
        ));
        $permissions->register('returns.view', 'Xem yêu cầu đổi/trả');
        $permissions->register('returns.manage', 'Duyệt, từ chối, nhận hàng trả');
        $permissions->register('returns.refund', 'Hoàn tất đổi/trả và hoàn tiền');

        $navigation->add('returns', 'Đổi/trả', 'admin.returns.home', 'returns.view', 70);
        Hook::onSlot('vani.admin.order.sidebar', fn ($order) => $this->app->make(OrderReturnsPanel::class)($order), priority: 20);

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('returns', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
