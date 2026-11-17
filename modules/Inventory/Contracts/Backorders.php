<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract đặt trước (ADR-047): hạn mức hiện hành và số đang chờ hàng — cho trang sản phẩm, trang đơn, chốt đẩy hãng.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface Backorders
{
    /**
     * Hạn mức (đã gộp mọi `BackorderPolicy`) và số đang chờ hàng tại kho bù tồn. SKU không đặt trước: bỏ qua.
     *
     * @param  list<int>  $variantIds
     * @return array<int, array{limit: int, waiting: int, remaining: int, expected_on: ?string}>
     */
    public function status(array $variantIds): array;

    /**
     * Số còn chờ hàng của một lần giữ hàng (đơn), hàng về lấp cho đơn giữ trước (FIFO). Không chờ gì → [].
     *
     * @return array<int, int> variant id => số lượng chờ
     */
    public function waitingFor(string $reservationKey): array;
}
