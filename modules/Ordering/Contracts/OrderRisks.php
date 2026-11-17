<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\RiskSignal;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (EP v3 X2): tín hiệu rủi ro đã gộp từ mọi `OrderRiskSignal` đang bật — cho Admin và cho tự động hoá
 * (vd. `vani.cod` chế độ khách quen bỏ qua đơn có tín hiệu `high`).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface OrderRisks
{
    /**
     * @param  list<int>  $orderIds
     * @return array<int, list<RiskSignal>>
     */
    public function forOrders(array $orderIds): array;

    public function hasHigh(int $orderId): bool;
}
