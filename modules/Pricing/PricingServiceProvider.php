<?php

declare(strict_types=1);

namespace Modules\Pricing;

use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Pricing\Application\PriceListPriorityStrategy;
use Modules\Pricing\Application\StrategyPriceResolver;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;

final class PricingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Pricing';
    }

    public function register(): void
    {
        $this->app->singleton(PriceListPriorityStrategy::class);
        $this->app->make(Extensions::class)->tag([PriceListPriorityStrategy::class], StrategyPriceResolver::TAG);
        $this->app->bind(PriceResolver::class, fn ($app): StrategyPriceResolver => new StrategyPriceResolver(
            $app->make(Extensions::class), $app->make(Settings::class), $app->make(CurrentContext::class), (string) config('vanishop.pricing.strategy', 'price_list_priority'),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'pricing.strategy', 'Cách chọn giá bán', 'select', (string) config('vanishop.pricing.strategy', 'price_list_priority'),
            [SettingsScope::OWNER, SettingsScope::BRAND, SettingsScope::CHANNEL], optionsFromTag: PricingStrategy::TAG, help: 'PricingStrategy dùng cho kênh/brand.',
        ));
        $permissions->register('pricing.view', 'Xem bảng giá của brand');
        $permissions->register('pricing.manage', 'Sửa bảng giá và giá bán của brand');

        $navigation->add('pricing', 'Giá bán', 'admin.pricing.home', 'pricing.view', 200);

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadBrandWorkspaceRoutes('pricing', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
