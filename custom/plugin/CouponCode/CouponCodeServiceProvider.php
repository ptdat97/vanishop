<?php

declare(strict_types=1);

namespace Plugin\CouponCode;

use Illuminate\Support\Facades\App;
use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Storefront\Contracts\CurrentCart;
use Modules\Storefront\Contracts\Data\SlotView;
use Plugin\CouponCode\Infrastructure\CouponPresenter;
use Plugin\CouponCode\Persistence\Coupon;

final class CouponCodeServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.coupon-code';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        // Mã công khai hiện ở trang sản phẩm (cache được): lưu/xoá mã → cache trang hết hiệu lực (ADR-051).
        $this->pageCacheModels([Coupon::class]);
        $this->translations($this->pluginPath('Resources/lang'), 'vani-coupon-code');
        $this->permissions([
            'coupon_code.view' => 'vani-coupon-code::texts.permission_view',
            'coupon_code.manage' => 'vani-coupon-code::texts.permission_manage',
        ], [PermissionRegistry::PRESET_MARKETING]);

        $this->adminMenu('coupons', 'vani-coupon-code::texts.menu', 'admin.plugins.vani-coupon-code.index', 'coupon_code.view', 215, group: 'marketing');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('CouponCode', $this->pluginPath('Resources/js/Pages'));

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-coupon-code');
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));
        $this->accountRoutes($this->pluginPath('Http/routes/account.php'), ['vi' => 'ma-giam-gia', 'en' => 'vouchers']);
        $this->accountPage('vouchers', 'vani-coupon-code::texts.account.menu', 'storefront.p.vani-coupon-code.account.index', 320);

        // Ví voucher: trang giỏ và checkout là trang riêng của từng khách (không cache) — tính thử mã ngay khi dựng trang.
        $this->onSlot('vani.storefront.cart.after_summary', fn (): ?SlotView => $this->wallet('cart'));
        $this->onSlot('vani.storefront.checkout.order_summary', fn (): ?SlotView => $this->wallet('checkout'));
        $this->onSlot('vani.storefront.pdp.before_add_to_cart', function (array $product): ?SlotView {
            $offers = $this->app->make(CouponPresenter::class)->forProduct($product, App::getLocale(), now()->getTimestamp());

            return $offers === [] ? null : new SlotView('vani-coupon-code::pdp', ['offers' => $offers, 'scriptSrc' => vani_asset(self::ID, 'coupons.js')]);
        });
    }

    private function wallet(string $context): ?SlotView
    {
        $shopper = $this->app->make(CurrentCart::class);
        $cart = $shopper->currentKey();
        if ($cart === null) {
            return null;
        }
        $wallet = $this->app->make(CouponPresenter::class)->wallet($cart, $shopper->customerId(), App::getLocale(), now()->getTimestamp());

        return $wallet['items'] === [] ? null : new SlotView('vani-coupon-code::wallet', [...$wallet, 'context' => $context, 'scriptSrc' => vani_asset(self::ID, 'coupons.js')]);
    }
}
