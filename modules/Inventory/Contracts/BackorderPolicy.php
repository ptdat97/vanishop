<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\Contracts\Data\BackorderAllowance;
use Modules\Shared\Contracts\PluginApi;

/**
 * Extension point (tag vani.inventory.backorder_policies, ADR-047): hạn mức đặt trước theo SKU — số được giữ vượt tồn thật
 * tại kho bù tồn. Core không đếm "đã bán đặt trước": số chờ hàng suy ra từ tồn, huỷ đơn tự trả suất, hàng về tự lấp.
 * Chỉ áp cho sản phẩm có theo dõi tồn; nhiều policy cùng trả cho một SKU → lấy hạn mức lớn nhất.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface BackorderPolicy
{
    /** Tag extension point: plugin đóng góp qua `contribute(BackorderPolicy::TAG, …)`. */
    public const TAG = 'vani.inventory.backorder_policies';

    /**
     * @param  list<int>  $variantIds
     * @return array<int, BackorderAllowance> variant id => hạn mức (SKU không đặt trước: bỏ qua)
     */
    public function allowances(array $variantIds): array;
}
