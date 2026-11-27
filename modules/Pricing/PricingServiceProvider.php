<?php

declare(strict_types=1);

namespace Modules\Pricing;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Catalog\Events\ProductDuplicated;
use Modules\Catalog\Events\ProductListingProjected;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Pricing\Application\BasePriceImporter;
use Modules\Pricing\Application\Listeners\CopyDuplicatedPrices;
use Modules\Pricing\Application\Listeners\ProductListPrices;
use Modules\Pricing\Application\Listeners\SyncListPrices;
use Modules\Pricing\Application\Listeners\SyncPriceVariantIndex;
use Modules\Pricing\Application\ManagedPriceListService;
use Modules\Pricing\Application\NoCustomerGroups;
use Modules\Pricing\Application\PricedVariants;
use Modules\Pricing\Application\PriceListPriorityStrategy;
use Modules\Pricing\Application\PriceListScheduler;
use Modules\Pricing\Application\StrategyPriceResolver;
use Modules\Pricing\Application\VariantPriceFields;
use Modules\Pricing\Console\SyncListPricesCommand;
use Modules\Pricing\Contracts\CustomerGroupDirectory;
use Modules\Pricing\Contracts\Data\DiscountCap;
use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Pricing\Contracts\PriceImporter;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;
use Modules\Pricing\Events\PriceChanged;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

final class PricingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Pricing';
    }

    public function register(): void
    {
        // Màn hình Admin của module mở cho plugin thêm cột/lọc/thao tác/công cụ (EP v3 X1).
        $this->app->make(AdminScreen::class)->declareResource('price_list_item');
        $this->app->singleton(PriceListPriorityStrategy::class);
        $this->app->make(Extensions::class)->tag([PriceListPriorityStrategy::class], PricingStrategy::TAG);
        // Ma trận biến thể (variant-matrix §3.3): cột giá + dọn giá khi SKU bị xoá.
        $this->app->make(Extensions::class)->tag([VariantPriceFields::class], VariantFieldProvider::TAG);
        $this->app->make(Extensions::class)->tag([PricedVariants::class], VariantUsage::TAG);
        $this->app->make(Extensions::class)->requires(PricingStrategy::TAG, Requirement::ExactlyOne, 'pricing::texts.pricing_service_provider.price_selection_strategy');
        $this->app->bind(PriceImporter::class, BasePriceImporter::class);
        $this->app->bind(PriceListSchedule::class, PriceListScheduler::class);
        $this->app->bind(ManagedPriceLists::class, ManagedPriceListService::class);
        $this->app->bindIf(CustomerGroupDirectory::class, NoCustomerGroups::class); // module Customer ghi đè
        $this->app->bind(PriceResolver::class, fn ($app): StrategyPriceResolver => new StrategyPriceResolver(
            $app->make(Extensions::class), $app->make(Settings::class), (string) config('vanishop.pricing.strategy', 'price_list_priority'), $app->make(CustomerGroupDirectory::class),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        Hook::onSlot('vani.admin.product.list_columns', fn (array $productIds): ?array => $this->app->make(ProductListPrices::class)->column($productIds));
        Hook::onSlot('vani.admin.product.bulk_actions', fn (): array => $this->app->make(ProductListPrices::class)->bulkActions());
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'pricing.strategy', 'pricing::texts.pricing_service_provider.how_selling_price_chosen', 'select', (string) config('vanishop.pricing.strategy', 'price_list_priority'),
            optionsFromTag: PricingStrategy::TAG, help: 'pricing::texts.pricing_service_provider.pricingstrategy_used_store',
            group: SettingGroup::Sales,
        ));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', DiscountCap::SETTING, 'pricing::texts.settings.max_discount_percent', 'int', intdiv((int) config('vanishop.promotion.max_discount_bp', 10_000), 100),
            help: 'pricing::texts.settings.max_discount_percent_help', rules: ['min:1', 'max:100'], group: SettingGroup::Sales,
        ));
        $permissions->register('pricing.view', 'pricing::texts.pricing_service_provider.view_price_lists', [PermissionRegistry::PRESET_MARKETING, PermissionRegistry::PRESET_CONTENT, PermissionRegistry::PRESET_ANALYST]);
        $permissions->register('pricing.manage', 'pricing::texts.pricing_service_provider.edit_price_lists_selling_prices');

        $navigation->add('pricing', 'pricing::texts.pricing_service_provider.selling_price', 'admin.pricing.home', 'pricing.view', 120, group: 'catalog');

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('pricing', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();

        // Giá niêm yết thấp nhất của sản phẩm cho bộ lọc/sắp xếp theo giá (Catalog `ListPrices`): theo event + tự sửa lệch hằng ngày.
        Event::listen(PriceChanged::class, SyncListPrices::class);
        Event::listen(ProductDuplicated::class, CopyDuplicatedPrices::class);
        Event::listen(ProductListingProjected::class, SyncPriceVariantIndex::class);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:pricing:sync-list-prices')->dailyAt('03:10')->withoutOverlapping()->onOneServer();
        });
        if ($this->app->runningInConsole()) {
            $this->commands([SyncListPricesCommand::class]);
        }
    }
}
