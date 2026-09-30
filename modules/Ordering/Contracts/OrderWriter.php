<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderDraft;
use Modules\Ordering\Contracts\Data\PlacedOrder;

/**
 * Service contract: tạo đơn từ bản nháp đã tính xong. Chạy TRONG transaction PlaceOrder.
 */
interface OrderWriter
{
    public function create(OrderDraft $draft): PlacedOrder;

    /**
     * Chuyển mọi đơn của khách nguồn sang khách đích (hợp nhất khách hàng). Snapshot trên đơn giữ nguyên.
     * Không giới hạn brand (khách hàng là cấp Owner). Mỗi đơn có một dòng order_events.
     *
     * @return int số đơn đã chuyển
     */
    public function reassignCustomer(int $fromCustomerId, int $toCustomerId): int;
}
