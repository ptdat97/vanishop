<?php

declare(strict_types=1);

namespace Plugin\Ghn;

use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Extension\PluginServiceProvider;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Plugin\Ghn\Infrastructure\GhnCarrier;

final class GhnServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.ghn';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/ghn.php'), 'vani.ghn');

        $this->app->singleton(GhnCarrier::class, fn (): GhnCarrier => new GhnCarrier(
            token: (string) config('vani.ghn.token'),
            shopId: (string) config('vani.ghn.shop_id'),
            webhookSecret: (string) config('vani.ghn.webhook_secret'),
            services: (array) config('vani.ghn.services', []),
        ));
    }

    public function boot(): void
    {
        $this->contribute(ShippingCarrier::CARRIERS_TAG, GhnCarrier::class);
        $this->contribute(ShippingRateProvider::TAG, GhnCarrier::class);
    }
}
