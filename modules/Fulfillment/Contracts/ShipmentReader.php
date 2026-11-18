<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Fulfillment\Contracts\Data\ShipmentView;
use Modules\Shared\Contracts\PluginApi;

/**
 * Đọc vận đơn của một đơn (lines theo SKU, trạng thái) — mở cho plugin từ 0.5.36 (`vani.preorder` chặn đẩy hãng theo từng kiện, L37).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface ShipmentReader
{
    /**
     * @return list<ShipmentView>
     */
    public function forOrder(int $orderId): array;
}
