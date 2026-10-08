<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\ReplacementOrderRequest;
use Modules\Ordering\Contracts\Data\PlacedOrder;

/**
 * Service contract (0.3.32): tạo đơn thay thế khi đổi hàng (Returns gọi). Chạy TRONG transaction của nơi gọi: giữ hàng
 * thay thế, tạo đơn `source = exchange` liên kết `parent_order_id`, thuế theo TaxCalculator, khoản COD cho phần bù.
 */
interface ReplacementOrders
{
    /**
     * @throws ReplacementUnavailable variant không bán được / không đủ hàng
     */
    public function place(ReplacementOrderRequest $request): PlacedOrder;
}
