<?php

declare(strict_types=1);

namespace Modules\Pricing;

use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Pricing\Application\PriceListPriorityStrategy;
use Modules\Pricing\Application\StrategyPriceResolver;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
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
        $this->app->make(Extensions::class)->tag([PriceListPriorityStrategy::class], PricingStrategy::TAG);
        $this->app->make(Extensions::class)->requires(PricingStrategy::TAG, Requirement::ExactlyOne, 'Chiến lược chọn giá');
        $this->app->bind(PriceResolver::class, fn ($app): StrategyPriceResolver => new StrategyPriceResolver(
            $app->make(Extensions::class), $app->make(Settings::class), (string) config('vanishop.pricing.strategy', 'price_list_priority'),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'pricing.strategy', 'Cách chọn giá bán', 'select', (string) config('vanishop.pricing.strategy', 'price_list_priority'),
            optionsFromTag: PricingStrategy::TAG, help: 'PricingStrategy dùng cho cửa hàng.',
        ));
        $permissions->register('pricing.view', 'Xem bảng giá');
        $permissions->register('pricing.manage', 'Sửa bảng giá và giá bán');

        $navigation->add('pricing', 'Giá bán', 'admin.pricing.home', 'pricing.view', 200);

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('pricing', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
