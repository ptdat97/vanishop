<?php

declare(strict_types=1);

namespace Plugin\Wishlist;

use Modules\Customer\Events\CustomerAnonymized;
use Modules\Customer\Events\CustomerMerged;
use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Notification\Contracts\NotificationCatalog;
use Modules\Pricing\Events\PriceChanged;
use Modules\Storefront\Contracts\Data\SlotView;
use Plugin\Wishlist\Infrastructure\WishlistAlerts;
use Plugin\Wishlist\Infrastructure\Wishlists;

/**
 * Yêu thích (docs/05-plugin/specs/wishlist.md): nút tim ở thẻ sản phẩm và PDP, trang trong tài khoản, khách vãng lai lưu
 * ở trình duyệt rồi gộp khi đăng nhập, báo có hàng lại / giảm giá (tuỳ chọn, marketing).
 */
final class WishlistServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.wishlist';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-wishlist');
        $this->settings([
            ['key' => 'notify_back_in_stock', 'label' => 'vani-wishlist::texts.wishlist_service_provider.notify_customers_when_wishlisted_product', 'type' => 'bool', 'default' => false, 'help' => 'vani-wishlist::texts.wishlist_service_provider.marketing_message_only_sent_customers'],
            ['key' => 'notify_price_drop', 'label' => 'vani-wishlist::texts.wishlist_service_provider.notify_customers_when_wishlisted_product_2', 'type' => 'bool', 'default' => false, 'help' => 'vani-wishlist::texts.wishlist_service_provider.compared_price_when_added_last'],
        ], [PermissionRegistry::PRESET_MARKETING]);

        $variables = ['customer_name', 'product_name', 'product_url', 'price'];
        $catalog = $this->app->make(NotificationCatalog::class);
        $catalog->define(new NotificationType(WishlistAlerts::BACK_IN_STOCK, 'vani-wishlist::texts.wishlist_service_provider.wishlisted_product_back_stock', $variables, [
            'mail' => ['subject' => 'vani-wishlist::texts.wishlist_service_provider.product_name_back_stock', 'body' => 'vani-wishlist::texts.wishlist_service_provider.hi_customer_name_product_name'],
        ]));
        $catalog->define(new NotificationType(WishlistAlerts::PRICE_DROP, 'vani-wishlist::texts.wishlist_service_provider.wishlisted_product_sale', $variables, [
            'mail' => ['subject' => 'vani-wishlist::texts.wishlist_service_provider.product_name_sale', 'body' => 'vani-wishlist::texts.wishlist_service_provider.hi_customer_name_product_name_2'],
        ]));

        $this->onEvent(AvailabilityChanged::class, fn (AvailabilityChanged $event) => $this->app->make(WishlistAlerts::class)->variantsChanged($event->variantIds));
        $this->onEvent(PriceChanged::class, fn (PriceChanged $event) => $this->app->make(WishlistAlerts::class)->variantsChanged($event->variantIds));
        $this->onEvent(CustomerMerged::class, fn (CustomerMerged $event) => $this->app->make(Wishlists::class)->move($event->sourceId, $event->targetId));
        $this->onEvent(CustomerAnonymized::class, fn (CustomerAnonymized $event) => $this->app->make(Wishlists::class)->forget($event->customerId));

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-wishlist');
        $this->storefrontPages($this->pluginPath('Http/routes/guest.php'), ['vi' => 'yeu-thich', 'en' => 'wishlist']);
        $this->accountRoutes($this->pluginPath('Http/routes/account.php'), ['vi' => 'yeu-thich', 'en' => 'wishlist']);
        $this->accountPage('wishlist', 'vani-wishlist::storefront.wishlist', 'storefront.p.vani-wishlist.account.index', 300);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));

        // HTML giống nhau với mọi khách (trang cache CDN): JS gửi bằng fetch, token CSRF lấy từ dữ liệu phiên.
        $button = fn (array $product, bool $compact): SlotView => new SlotView('vani-wishlist::button', ['styleId' => (int) $product['id'], 'compact' => $compact]);
        $this->onSlot('vani.storefront.plp.card_badges', fn (array $product): SlotView => $button($product, true));
        $this->onSlot('vani.storefront.pdp.after_add_to_cart', fn (array $product): SlotView => $button($product, false));
        $this->onSlot('vani.storefront.header.actions', fn (): SlotView => new SlotView('vani-wishlist::header'));
        $this->onSlot('vani.storefront.layout.body_end', fn (): SlotView => new SlotView('vani-wishlist::script', [
            'scriptSrc' => vani_asset(self::ID, 'wishlist.js'),
            'urls' => ['ids' => route('api.storefront.x.vani-wishlist.ids'), 'items' => route('api.storefront.x.vani-wishlist.items.store'), 'merge' => route('api.storefront.x.vani-wishlist.items.merge')],
        ]));
    }
}
