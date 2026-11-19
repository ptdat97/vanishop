<?php

declare(strict_types=1);

namespace Plugin\CarrierRouting;

use Modules\Extension\PluginServiceProvider;
use Modules\Fulfillment\Contracts\CarrierAssignment;
use Modules\Identity\Contracts\PermissionRegistry;
use Plugin\CarrierRouting\Infrastructure\RoutingRules;

/**
 * Chọn hãng theo tuyến (EP v3 X3 — tham chiếu cho `CarrierAssignment`).
 */
final class CarrierRoutingServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.carrier-routing';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-carrier-routing');
        $this->settings([
            ['key' => 'rules', 'label' => 'vani-carrier-routing::texts.carrier_routing_service_provider.carrier_selection_rules', 'type' => 'text',
                'help' => 'vani-carrier-routing::texts.carrier_routing_service_provider.one_condition_carrier_service_per'],
        ], [PermissionRegistry::PRESET_ORDER_OPS]);
        $this->contribute(CarrierAssignment::TAG, RoutingRules::class);
    }
}
