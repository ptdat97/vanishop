<?php

declare(strict_types=1);

namespace Plugin\ShippingFlatRate;

use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Extension\PluginServiceProvider;
use Plugin\ShippingFlatRate\Infrastructure\FlatRateShipping;

/**
 * Plugin hệ thống (ADR-029): phí giao cố định + ngưỡng miễn phí.
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

    public function boot(): void
    {
        $this->translations($this->pluginPath('resources/lang'), 'vani-shipping-flat-rate');
        $this->settings([
            ['key' => 'fee', 'label' => 'Phí giao (₫)', 'type' => 'int', 'help' => 'Để trống = theo VANI_SHIPPING_FLAT_FEE.'],
            ['key' => 'free_over', 'label' => 'Miễn phí giao từ (₫)', 'type' => 'int', 'help' => 'Tiền hàng sau giảm giá. Để trống = theo VANI_SHIPPING_FREE_OVER.'],
        ]);

        $this->contribute(ShippingRateProvider::TAG, FlatRateShipping::class);
    }
}
