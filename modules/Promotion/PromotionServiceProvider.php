<?php

declare(strict_types=1);

namespace Modules\Promotion;

use Illuminate\Support\Facades\Event;
use Modules\Customer\Events\CustomerMerged;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Pricing\Contracts\Data\DiscountCap;
use Modules\Promotion\Application\Actions\AmountOffAction;
use Modules\Promotion\Application\Actions\PercentOffAction;
use Modules\Promotion\Application\CampaignService;
use Modules\Promotion\Application\Listeners\MergeCustomerPromotions;
use Modules\Promotion\Application\ManagedPromotionService;
use Modules\Promotion\Application\PromotionEvaluator;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Application\VoucherService;
use Modules\Promotion\Contracts\Campaigns;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Settings;

final class PromotionServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Promotion';
    }

    public function register(): void
    {
        // Màn hình Admin của module mở cho plugin thêm cột/lọc/thao tác/công cụ (EP v3 X1).
        $this->app->make(AdminScreen::class)->declareResource('promotion');
        $this->app->make(AdminScreen::class)->declareResource('campaign');
        $this->app->bind(Campaigns::class, CampaignService::class);
        $this->app->bind(ManagedPromotions::class, ManagedPromotionService::class);
        $this->app->bind(Vouchers::class, VoucherService::class);
        $this->app->make(Extensions::class)->tag([PercentOffAction::class, AmountOffAction::class], PromotionRegistry::ACTIONS_TAG);
        $this->app->make(Extensions::class)->kindContract('promotion', PromotionRule::TAG);
        $this->app->bind(PromotionEngine::class, fn ($app): PromotionEvaluator => new PromotionEvaluator(
            $app->make(PromotionRegistry::class),
            // Giá sàn là bất biến của engine; mức trần do cửa hàng cấu hình (VN: 50%, NĐ 81/2018) — Admin → Cài đặt → Bán
            // hàng & thuế, đọc lúc dùng (mặc định VANI_PROMOTION_MAX_DISCOUNT_BP).
            DiscountCap::basisPoints($app->make(Settings::class)->get('core', DiscountCap::SETTING)),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('promotion.view', 'promotion::texts.promotion_service_provider.view_promotions', [PermissionRegistry::PRESET_MARKETING, PermissionRegistry::PRESET_ANALYST]);
        $permissions->register('promotion.manage', 'promotion::texts.promotion_service_provider.create_edit_promotions_vouchers', [PermissionRegistry::PRESET_MARKETING]);

        $navigation->group('marketing', 'Marketing', 205);
        $navigation->add('promotion', 'promotion::texts.promotion_service_provider.promotion', 'admin.promotion.home', 'promotion.view', 210, group: 'marketing');

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('promotion', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();

        Event::listen(CustomerMerged::class, MergeCustomerPromotions::class);
    }
}
