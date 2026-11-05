<?php

declare(strict_types=1);

namespace Plugin\Wishlist;

use Modules\Customer\Events\CustomerAnonymized;
use Modules\Customer\Events\CustomerMerged;
use Modules\Extension\PluginServiceProvider;
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
        $this->loadJsonTranslationsFrom($this->pluginPath('Resources/lang'));
        $this->settings([
            ['key' => 'notify_back_in_stock', 'label' => 'Báo khách khi sản phẩm yêu thích có hàng lại', 'type' => 'bool', 'default' => false, 'help' => 'Tin marketing: chỉ gửi cho khách đồng ý nhận tin. Mẫu tin ở Admin → Thông báo.'],
            ['key' => 'notify_price_drop', 'label' => 'Báo khách khi sản phẩm yêu thích giảm giá', 'type' => 'bool', 'default' => false, 'help' => 'So với giá lúc thêm (hoặc lần báo trước). Tối đa một tin mỗi sản phẩm trong 7 ngày.'],
        ]);

        $variables = ['customer_name', 'product_name', 'product_url', 'price'];
        $catalog = $this->app->make(NotificationCatalog::class);
        $catalog->define(new NotificationType(WishlistAlerts::BACK_IN_STOCK, 'Sản phẩm yêu thích có hàng lại', $variables, [
            'mail' => ['subject' => '{{ product_name }} đã có hàng lại', 'body' => "Chào {{ customer_name }},\n\n{{ product_name }} trong danh sách yêu thích của bạn đã có hàng lại: {{ product_url }}"],
        ]));
        $catalog->define(new NotificationType(WishlistAlerts::PRICE_DROP, 'Sản phẩm yêu thích giảm giá', $variables, [
            'mail' => ['subject' => '{{ product_name }} đang giảm giá', 'body' => "Chào {{ customer_name }},\n\n{{ product_name }} trong danh sách yêu thích của bạn giảm còn {{ price }}: {{ product_url }}"],
        ]));

        $this->onEvent(AvailabilityChanged::class, fn (AvailabilityChanged $event) => $this->app->make(WishlistAlerts::class)->variantsChanged($event->variantIds));
        $this->onEvent(PriceChanged::class, fn (PriceChanged $event) => $this->app->make(WishlistAlerts::class)->variantsChanged($event->variantIds));
        $this->onEvent(CustomerMerged::class, fn (CustomerMerged $event) => $this->app->make(Wishlists::class)->move($event->sourceId, $event->targetId));
        $this->onEvent(CustomerAnonymized::class, fn (CustomerAnonymized $event) => $this->app->make(Wishlists::class)->forget($event->customerId));

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-wishlist');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'));
        $this->accountRoutes($this->pluginPath('Http/routes/account.php'), ['vi' => 'yeu-thich', 'en' => 'wishlist']);
        $this->accountPage('wishlist', 'Yêu thích', 'storefront.p.vani-wishlist.account.index', 300);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));

        $this->onSlot('vani.storefront.plp.card_badges', fn (array $product): SlotView => new SlotView('vani-wishlist::button', ['styleId' => (int) $product['id'], 'compact' => true]));
        $this->onSlot('vani.storefront.pdp.after_add_to_cart', fn (array $product): SlotView => new SlotView('vani-wishlist::button', ['styleId' => (int) $product['id'], 'compact' => false]));
        $this->onSlot('vani.storefront.header.actions', fn (): SlotView => new SlotView('vani-wishlist::header'));
        $this->onSlot('vani.storefront.layout.body_end', fn (): SlotView => new SlotView('vani-wishlist::script'));
    }
}
