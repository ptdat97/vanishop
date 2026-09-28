<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\CheckoutQuote;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\PlaceOrderResult;

/**
 * Service contract: xem tổng tiền (không ghi gì) và đặt hàng (một transaction, idempotent theo key).
 */
interface Checkout
{
    public function quote(CheckoutRequest $request): CheckoutQuote;

    public function placeOrder(CheckoutRequest $request, string $idempotencyKey): PlaceOrderResult;
}
