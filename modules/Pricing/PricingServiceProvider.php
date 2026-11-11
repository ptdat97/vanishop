<?php

declare(strict_types=1);

namespace Modules\Pricing;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Pricing\Application\BasePriceImporter;
use Modules\Pricing\Application\Listeners\ProductListPrices;
use Modules\Pricing\Application\Listeners\SyncListPrices;
use Modules\Pricing\Application\NoCustomerGroups;
use Modules\Pricing\Application\PriceListPriorityStrategy;
use Modules\Pricing\Application\PriceListScheduler;
use Modules\Pricing\Application\StrategyPriceResolver;
use Modules\Pricing\Console\SyncListPricesCommand;
use Modules\Pricing\Contracts\CustomerGroupDirectory;
use Modules\Pricing\Contracts\PriceImporter;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;
use Modules\Pricing\Events\PriceChanged;
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
        $this->app->bind(PriceImporter::class, BasePriceImporter::class);
        $this->app->bind(PriceListSchedule::class, PriceListScheduler::class);
        $this->app->bindIf(CustomerGroupDirectory::class, NoCustomerGroups::class); // module Customer ghi đè
        $this->app->bind(PriceResolver::class, fn ($app): StrategyPriceResolver => new StrategyPriceResolver(
            $app->make(Extensions::class), $app->make(Settings::class), (string) config('vanishop.pricing.strategy', 'price_list_priority'), $app->make(CustomerGroupDirectory::class),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        Hook::onSlot('vani.admin.product.list_columns', fn (array $styleIds): ?array => $this->app->make(ProductListPrices::class)->column($styleIds));
        Hook::onSlot('vani.admin.product.bulk_actions', fn (): array => $this->app->make(ProductListPrices::class)->bulkActions());
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'pricing.strategy', 'Cách chọn giá bán', 'select', (string) config('vanishop.pricing.strategy', 'price_list_priority'),
            optionsFromTag: PricingStrategy::TAG, help: 'PricingStrategy dùng cho cửa hàng.',
        ));
        $permissions->register('pricing.view', 'Xem bảng giá');
        $permissions->register('pricing.manage', 'Sửa bảng giá và giá bán');

        $navigation->add('pricing', 'Giá bán', 'admin.pricing.home', 'pricing.view', 120, group: 'catalog');

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('pricing', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();

        // Giá niêm yết thấp nhất của sản phẩm cho bộ lọc/sắp xếp theo giá (Catalog `ListPrices`): theo event + tự sửa lệch hằng ngày.
        Event::listen(PriceChanged::class, SyncListPrices::class);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:pricing:sync-list-prices')->dailyAt('03:10')->withoutOverlapping()->onOneServer();
        });
        if ($this->app->runningInConsole()) {
            $this->commands([SyncListPricesCommand::class]);
        }
    }
}
