<?php

declare(strict_types=1);

namespace Plugin\ShippingFlatRate;

use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Extension\PluginServiceProvider;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\ShippingFlatRate\Infrastructure\CartProgressEnricher;
use Plugin\ShippingFlatRate\Infrastructure\FlatRateShipping;
use Plugin\ShippingFlatRate\Infrastructure\FreeShippingProgress;

/**
 * Plugin hệ thống (ADR-029): phí giao cố định + ngưỡng miễn phí; thanh tiến độ miễn phí giao (1.1.0).
 */
final class ShippingFlatRateServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.shipping-flat-rate';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/shipping-flat-rate.php'), 'vani.shipping-flat-rate');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        $definitions = [
            ['key' => 'fee', 'label' => 'Phí giao (₫)', 'type' => 'int', 'help' => 'Để trống = theo VANI_SHIPPING_FLAT_FEE.'],
            ['key' => 'free_over', 'label' => 'Miễn phí giao từ (₫)', 'type' => 'int', 'help' => 'Tiền hàng sau giảm giá. Để trống = theo VANI_SHIPPING_FREE_OVER.'],
            ['key' => 'progress_enabled', 'label' => 'Hiện thanh tiến độ miễn phí giao', 'type' => 'bool', 'default' => true],
            ['key' => 'progress_on_cart', 'label' => 'Thanh tiến độ ở trang giỏ', 'type' => 'bool', 'default' => true],
            ['key' => 'progress_on_checkout', 'label' => 'Thanh tiến độ ở trang thanh toán', 'type' => 'bool', 'default' => true],
            ['key' => 'progress_on_pdp', 'label' => 'Ngưỡng miễn phí giao ở trang sản phẩm', 'type' => 'bool', 'default' => false],
            ['key' => 'progress_color', 'label' => 'Màu thanh tiến độ', 'help' => 'Dạng #rrggbb; trống = màu nhấn của giao diện.', 'rules' => ['regex:/^#[0-9a-fA-F]{6}$/']],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $definitions[] = ['key' => "progress_message_pending.{$locale}", 'label' => "Thông báo khi chưa đạt ({$locale})", 'help' => 'Bắt buộc có {amount} — số tiền còn thiếu.', 'rules' => ['regex:/\{amount\}/']];
            $definitions[] = ['key' => "progress_message_achieved.{$locale}", 'label' => "Thông báo khi đã đạt ({$locale})"];
        }

        return $definitions;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('resources/lang'), 'vani-shipping-flat-rate');
        $this->settings($this->definitions());

        $this->contribute(ShippingRateProvider::TAG, FlatRateShipping::class);

        // Thanh tiến độ miễn phí giao (1.1.0) — cùng ngưỡng `free_over` với phí giao.
        $this->storefrontViews($this->pluginPath('resources/views'), 'vani-shipping-flat-rate');
        $this->contribute(StorefrontEnricher::TAG, CartProgressEnricher::class);
        $progress = fn (): FreeShippingProgress => $this->app->make(FreeShippingProgress::class);
        $this->onSlot('vani.storefront.cart.after_lines', function (array $cart) use ($progress): ?SlotView {
            $data = $cart['extensions'][self::ID]['free_shipping'] ?? null;

            return $data !== null && $progress()->shownOn('cart') ? new SlotView('vani-shipping-flat-rate::progress', ['progress' => $data]) : null;
        });
        $this->onSlot('vani.storefront.checkout.after_shipping', function (array $quote) use ($progress): ?SlotView {
            if (! $progress()->shownOn('checkout') || $quote['lines'] === []) {
                return null;
            }
            // Tiền hàng sau giảm giá (giống cách tính phí giao), không gồm phí giao.
            $merchandise = array_sum(array_map(fn (array $line): int => (int) $line['total']['amount'], $quote['lines']));
            $data = $progress()->forAmount($merchandise, (string) $quote['currency']);

            return $data === null ? null : new SlotView('vani-shipping-flat-rate::progress', ['progress' => $data]);
        });
        $this->onSlot('vani.storefront.pdp.after_add_to_cart', function () use ($progress): ?SlotView {
            $threshold = $progress()->threshold();

            return $threshold !== null && $progress()->shownOn('pdp')
                ? new SlotView('vani-shipping-flat-rate::threshold', ['amount' => app(MoneyFormatter::class)->formatAmount($threshold, (string) config('vanishop.currency'))])
                : null;
        });
    }
}
