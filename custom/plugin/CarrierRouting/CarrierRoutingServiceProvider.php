<?php

declare(strict_types=1);

namespace Plugin\CarrierRouting;

use Modules\Extension\PluginServiceProvider;
use Modules\Fulfillment\Contracts\CarrierAssignment;
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
        $this->settings([
            ['key' => 'rules', 'label' => 'Quy tắc chọn hãng', 'type' => 'text',
                'help' => 'Mỗi dòng "điều kiện = hãng[:dịch vụ]", dòng đầu tiên khớp thắng. Điều kiện: "tinh:79,01" (mã tỉnh/thành), "cod>=3000000" (thu hộ từ mức), "*" (mọi đơn). Vd. "tinh:79 = ahamove".'],
        ]);
        $this->contribute(CarrierAssignment::TAG, RoutingRules::class);
    }
}
