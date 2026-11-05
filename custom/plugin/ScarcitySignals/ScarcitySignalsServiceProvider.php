<?php

declare(strict_types=1);

namespace Plugin\ScarcitySignals;

use Modules\Extension\PluginServiceProvider;
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
        $this->loadJsonTranslationsFrom($this->pluginPath('Resources/lang'));
        $this->settings([
            ['key' => 'show_low_stock', 'label' => 'Hiện "Chỉ còn N" ở trang sản phẩm', 'type' => 'bool', 'default' => true, 'help' => 'Theo đúng size khách đang chọn, chỉ khi số bán được từ ngưỡng trở xuống.'],
            ['key' => 'low_stock_threshold', 'label' => 'Ngưỡng "sắp hết" (1–100)', 'type' => 'int', 'default' => 5, 'rules' => ['min:1', 'max:100']],
            ['key' => 'threshold_overrides', 'label' => 'Ngưỡng riêng theo danh mục', 'type' => 'text',
                'help' => 'Mỗi dòng "danh mục = ngưỡng", danh mục là đường dẫn hoặc id, vd. "ao-thun = 10", "vay-cuoi = 1". Áp cho danh mục chính và danh mục cha của sản phẩm; nhiều ngưỡng thì lấy nhỏ nhất.'],
            ['key' => 'show_list_label', 'label' => 'Nhãn "Sắp hết" ở thẻ sản phẩm', 'type' => 'bool', 'default' => true, 'help' => 'Theo tổng số bán được của mọi size và ngưỡng chung.'],
            ['key' => 'show_sold_count', 'label' => 'Hiện "Đã bán N trong X ngày"', 'type' => 'bool', 'default' => true, 'help' => 'Chỉ đơn đã thu tiền hoặc đã giao cho hãng vận chuyển; tính lại mỗi giờ.'],
            ['key' => 'sold_window_days', 'label' => 'Cửa sổ thống kê (ngày, 1–90)', 'type' => 'int', 'default' => 7, 'rules' => ['min:1', 'max:90']],
            ['key' => 'sold_min_to_show', 'label' => 'Chỉ hiện "Đã bán" khi từ (1–1000)', 'type' => 'int', 'default' => 3, 'rules' => ['min:1', 'max:1000'],
                'help' => '"Đã bán 1" là bằng chứng ngược (gần như không ai mua) — dưới mức này thì giấu, không làm đẹp số.'],
        ]);

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-scarcity');
        $this->contribute(StorefrontEnricher::TAG, ProductSignalsEnricher::class);
        $this->contribute(StorefrontEnricher::TAG, CardSignalsEnricher::class);

        $this->onSlot('vani.storefront.pdp.add_to_cart_fields', fn (array $product): ?SlotView => (array) ($product['extensions'][self::ID]['low_stock'] ?? []) === []
            ? null
            : new SlotView('vani-scarcity::pdp-left', ['lowStock' => $product['extensions'][self::ID]['low_stock']]));
        $this->onSlot('vani.storefront.pdp.after_price', fn (array $product): ?SlotView => ($sold = $product['extensions'][self::ID]['sold'] ?? null) === null
            ? null
            : new SlotView('vani-scarcity::pdp-sold', ['sold' => $sold]));
        $this->onSlot('vani.storefront.plp.card_badges', fn (array $product): ?SlotView => ($product['extensions'][self::ID]['low_stock'] ?? false) === true
            ? new SlotView('vani-scarcity::card-badge')
            : null);
    }
}
