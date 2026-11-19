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
            ['key' => 'fee', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.shipping_fee', 'type' => 'int', 'help' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.leave_empty_use_vani_shipping'],
            ['key' => 'free_over', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.free_shipping_from', 'type' => 'int', 'help' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.merchandise_total_after_discounts_leave'],
            // Phụ phí theo khối lượng (1.2.0): khối lượng giỏ (gram, dòng đã chọn) do Checkout truyền vào.
            ['key' => 'included_grams', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.weight_included_shipping_fee_grams', 'type' => 'int', 'help' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.above_surcharge_per_kg_rounded', 'rules' => ['min:0']],
            ['key' => 'fee_per_extra_kg', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.surcharge_per_extra_kg', 'type' => 'int', 'rules' => ['min:0']],
            ['key' => 'progress_enabled', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.show_free_shipping_progress_bar', 'type' => 'bool', 'default' => true],
            ['key' => 'progress_on_cart', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.progress_bar_cart_page', 'type' => 'bool', 'default' => true],
            ['key' => 'progress_on_checkout', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.progress_bar_checkout_page', 'type' => 'bool', 'default' => true],
            ['key' => 'progress_on_pdp', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.free_shipping_threshold_product_page', 'type' => 'bool', 'default' => false],
            ['key' => 'progress_color', 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.progress_bar_colour', 'help' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.format_rrggbb_empty_theme_s', 'rules' => ['regex:/^#[0-9a-fA-F]{6}$/']],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $definitions[] = ['key' => "progress_message_pending.{$locale}", 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.message_when_not_reached_locale', 'help' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.must_contain_amount_remaining_amount', 'rules' => ['regex:/\{amount\}/']];
            $definitions[] = ['key' => "progress_message_achieved.{$locale}", 'label' => 'vani-shipping-flat-rate::texts.shipping_flat_rate_service_provider.message_when_reached_locale'];
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
        $this->onSlot('vani.storefront.checkout.contact_before', function (array $quote) use ($progress): ?SlotView {
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
