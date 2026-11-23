<?php

declare(strict_types=1);

namespace Plugin\Bundles;

use Database\Seeders\Demo\DemoContent;
use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\Bundles\Database\BundlesDemoContent;
use Plugin\Bundles\Infrastructure\BundleAction;
use Plugin\Bundles\Infrastructure\BundleBlock;
use Plugin\Bundles\Infrastructure\BundlePageSurface;
use Plugin\Bundles\Infrastructure\BundlePresenter;
use Plugin\Bundles\Infrastructure\BundleRule;

/**
 * Combo sản phẩm: mua đủ bộ (vd. áo + váy + túi) giảm theo %. Mỗi combo đồng bộ thành một khuyến mãi của Core
 * (ManagedPromotions) với rule "Mua đủ combo" và action giảm theo số bộ (ContextualPromotionAction). Storefront: khung
 * "Mua trọn bộ" ở trang sản phẩm (slot `pdp.after_details`) và block Combo cho Builder; thêm cả bộ bằng Carts::addLines.
 */
final class BundlesServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.bundles';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole() && ! $this->app->isProduction()) {
            $this->contribute(DemoContent::TAG, BundlesDemoContent::class);
        }
        $this->translations($this->pluginPath('Resources/lang'), 'vani-bundles');
        $this->permissions([
            'bundles.view' => 'vani-bundles::texts.permission_view',
            'bundles.manage' => 'vani-bundles::texts.permission_manage',
        ], [PermissionRegistry::PRESET_MARKETING]);

        $this->contribute(PromotionRule::TAG, BundleRule::class);
        $this->contribute(PromotionAction::TAG, BundleAction::class);
        $this->contribute(StorefrontBlock::TAG, BundleBlock::class);
        $this->publishHooks($this->pluginPath('hooks.php'));
        $this->contribute(BuilderSurface::TAG, BundlePageSurface::class);

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-bundles');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'), prefix: ['vi' => 'combo', 'en' => 'bundles'], cacheable: true);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));
        $this->onSlot('vani.storefront.pdp.after_details', function (array $product): ?SlotView {
            $bundles = $this->app->make(BundlePresenter::class)->forStyle((int) $product['id'], $this->app->getLocale());

            return $bundles === [] ? null : new SlotView('vani-bundles::pdp', ['bundles' => $bundles, 'scriptSrc' => vani_asset(self::ID, 'bundles.js')]);
        });

        $this->adminMenu('bundles', 'vani-bundles::texts.menu', 'admin.plugins.vani-bundles.index', 'bundles.view', 110, group: 'catalog');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('Bundles', $this->pluginPath('Resources/js/Pages'));
    }
}
