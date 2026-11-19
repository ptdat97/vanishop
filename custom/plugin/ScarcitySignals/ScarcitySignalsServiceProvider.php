<?php

declare(strict_types=1);

namespace Plugin\ScarcitySignals;

use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\ScarcitySignals\Infrastructure\CardSignalsEnricher;
use Plugin\ScarcitySignals\Infrastructure\ProductSignalsEnricher;

/**
 * Tín hiệu khan hiếm (docs/05-plugin/specs/scarcity-signals.md): "Chỉ còn N" theo size đang chọn, "Đã bán N trong X ngày"
 * (đơn đã thu tiền hoặc đã rời kho), nhãn "Sắp hết" ở thẻ sản phẩm. Không có chế độ bịa số, không có "N người đang xem".
 */
final class ScarcitySignalsServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.scarcity-signals';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-scarcity-signals');
        $this->settings([
            ['key' => 'show_low_stock', 'label' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.show_only_n_left_product', 'type' => 'bool', 'default' => true, 'help' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.size_customer_choosing_only_when'],
            ['key' => 'low_stock_threshold', 'label' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.low_stock_threshold_1_100', 'type' => 'int', 'default' => 5, 'rules' => ['min:1', 'max:100']],
            ['key' => 'threshold_overrides', 'label' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.per_category_thresholds', 'type' => 'text',
                'help' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.one_category_threshold_per_line'],
            ['key' => 'show_list_label', 'label' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.low_stock_badge_product_cards', 'type' => 'bool', 'default' => true, 'help' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.based_total_sellable_quantity_every'],
            ['key' => 'show_sold_count', 'label' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.show_sold_n_x_days', 'type' => 'bool', 'default' => true, 'help' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.only_orders_paid_handed_carrier'],
            ['key' => 'sold_window_days', 'label' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.statistics_window_days_1_90', 'type' => 'int', 'default' => 7, 'rules' => ['min:1', 'max:90']],
            ['key' => 'sold_min_to_show', 'label' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.only_show_sold_from_1', 'type' => 'int', 'default' => 3, 'rules' => ['min:1', 'max:1000'],
                'help' => 'vani-scarcity-signals::texts.scarcity_signals_service_provider.sold_1_counter_evidence_almost'],
        ], [PermissionRegistry::PRESET_MARKETING]);

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-scarcity');
        $this->contribute(StorefrontEnricher::TAG, ProductSignalsEnricher::class);
        $this->contribute(StorefrontEnricher::TAG, CardSignalsEnricher::class);

        $this->onSlot('vani.storefront.pdp.add_to_cart_fields', fn (array $product): ?SlotView => (array) ($product['extensions'][self::ID]['low_stock'] ?? []) === []
            ? null
            : new SlotView('vani-scarcity::pdp-left', ['lowStock' => $product['extensions'][self::ID]['low_stock'], 'scriptSrc' => vani_asset(self::ID, 'scarcity.js')]));
        $this->onSlot('vani.storefront.pdp.after_price', fn (array $product): ?SlotView => ($sold = $product['extensions'][self::ID]['sold'] ?? null) === null
            ? null
            : new SlotView('vani-scarcity::pdp-sold', ['sold' => $sold]));
        $this->onSlot('vani.storefront.plp.card_badges', fn (array $product): ?SlotView => ($product['extensions'][self::ID]['low_stock'] ?? false) === true
            ? new SlotView('vani-scarcity::card-badge')
            : null);
    }
}
