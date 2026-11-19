<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras;

use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\BannerGridBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\BestsellersBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\FeaturesBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\MarkdownBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\ProductTabsBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\RecentlyViewedBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\SlideshowBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\VideoHeroBlock;

/**
 * Khối page builder bổ sung (docs/05-plugin/specs/storefront-extras.md, reference-parity §3.16-4) + thanh "Đã xem" ở giỏ.
 * Thanh "Đã xem" ở trang sản phẩm do theme `vani-base` lo sẵn (Core 0.3.79) nên plugin không thêm lần nữa.
 */
final class StorefrontExtrasServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.storefront-extras';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-storefront-extras');
        $this->settings([
            ['key' => 'recent_on_cart', 'label' => 'vani-storefront-extras::texts.storefront_extras_service_provider.recently_viewed_bar_cart_page', 'type' => 'bool', 'default' => true],
            ['key' => 'recent_on_cart_limit', 'label' => 'vani-storefront-extras::texts.storefront_extras_service_provider.products_cart_recently_viewed_bar', 'type' => 'int', 'default' => 12, 'rules' => ['min:1', 'max:20']],
        ], [PermissionRegistry::PRESET_CONTENT]);

        foreach ([BestsellersBlock::class, RecentlyViewedBlock::class, VideoHeroBlock::class, SlideshowBlock::class, BannerGridBlock::class, ProductTabsBlock::class, FeaturesBlock::class, MarkdownBlock::class] as $block) {
            $this->contribute(StorefrontBlock::TAG, $block);
        }
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-storefront-extras');

        $this->onSlot('vani.storefront.cart.after_lines', function (array $cart): ?SlotView {
            if (! (bool) vani_setting(self::ID, 'recent_on_cart', true)) {
                return null;
            }

            return new SlotView('vani-storefront-extras::blocks.recently-viewed', [
                'title' => __('vani-storefront-extras::storefront.recently_viewed_title'), 'layout' => 'scroll',
                'limit' => max(1, min(20, (int) vani_setting(self::ID, 'recent_on_cart_limit', 12))),
                // Bỏ sản phẩm đã có trong giỏ.
                'exclude' => array_values(array_filter(array_map(fn (array $line): ?string => $line['product']['slug'] ?? null, (array) ($cart['lines'] ?? [])))),
            ]);
        });
        $this->onSlot('vani.storefront.layout.body_end', fn (): SlotView => new SlotView('vani-storefront-extras::script', ['src' => vani_asset(self::ID, 'extras.js')]));
    }
}
