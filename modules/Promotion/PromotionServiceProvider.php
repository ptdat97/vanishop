<?php

declare(strict_types=1);

namespace Modules\Promotion;

use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Promotion\Application\Actions\AmountOffAction;
use Modules\Promotion\Application\Actions\PercentOffAction;
use Modules\Promotion\Application\PromotionEvaluator;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Shared\Support\ModuleServiceProvider;

final class PromotionServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Promotion';
    }

    public function register(): void
    {
        $this->app->make(Extensions::class)->tag([PercentOffAction::class, AmountOffAction::class], PromotionRegistry::ACTIONS_TAG);
        $this->app->make(Extensions::class)->kindContract('promotion', PromotionRule::TAG);
        $this->app->bind(PromotionEngine::class, fn ($app): PromotionEvaluator => new PromotionEvaluator(
            $app->make(PromotionRegistry::class),
            // Giá sàn là bất biến của engine; mức trần do cửa hàng cấu hình (VN: 50%, NĐ 81/2018). Không cấu hình → không giới hạn.
            (int) config('vanishop.promotion.max_discount_bp', PromotionEvaluator::NO_CAP_BP),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('promotion.view', 'Xem khuyến mãi');
        $permissions->register('promotion.manage', 'Tạo/sửa khuyến mãi và voucher');

        $navigation->add('promotion', 'Khuyến mãi', 'admin.promotion.home', 'promotion.view', 250, group: 'marketing');

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('promotion', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
