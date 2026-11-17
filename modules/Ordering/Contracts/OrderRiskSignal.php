<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\RiskSignal;
use Modules\Shared\Contracts\PluginApi;

/**
 * Extension point (tag `vani.ordering.risk_signals`, EP v3 X2): plugin chấm rủi ro đơn (bom hàng, gian lận…) cho chốt ①
 * của ADR-046. Core gộp tín hiệu của mọi plugin, hiện trên dòng "Cần xác nhận" và trang đơn; nhân viên vẫn là người quyết
 * định (muốn chặn xác nhận thì dùng hook `vani.order.before_confirm`). Gọi theo lô, chỉ đọc, không I/O mạng chậm — lỗi
 * plugin bị bỏ qua (trang vẫn mở).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface OrderRiskSignal
{
    public const TAG = 'vani.ordering.risk_signals';

    /**
     * @param  list<int>  $orderIds
     * @return array<int, list<RiskSignal>> id đơn => tín hiệu (đơn không có tín hiệu thì bỏ)
     */
    public function assess(array $orderIds): array;
}
