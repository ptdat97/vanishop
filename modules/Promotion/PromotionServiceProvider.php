<?php

declare(strict_types=1);

namespace Modules\Promotion;

use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Promotion\Application\Actions\AmountOffAction;
use Modules\Promotion\Application\Actions\PercentOffAction;
use Modules\Promotion\Application\PromotionEvaluator;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Shared\Support\ModuleServiceProvider;

final class PromotionServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Promotion';
    }

    public function register(): void
    {
        $this->app->tag([PercentOffAction::class, AmountOffAction::class], PromotionRegistry::ACTIONS_TAG);
        $this->app->bind(PromotionEngine::class, fn ($app): PromotionEvaluator => new PromotionEvaluator(
            $app->make(PromotionRegistry::class),
            (int) config('vanishop.promotion.max_discount_bp', 5000),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('promotion.view', 'Xem khuyến mãi của brand');
        $permissions->register('promotion.manage', 'Tạo/sửa khuyến mãi và voucher của brand');

        $navigation->add('promotion', 'Khuyến mãi', 'admin.promotion.home', 'promotion.view', 250);

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadBrandWorkspaceRoutes('promotion', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
