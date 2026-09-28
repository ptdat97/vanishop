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
}
