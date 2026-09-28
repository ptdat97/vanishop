<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\CheckoutIssue;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\Totals;

/**
 * Extension point (tag `vani.checkout.validators`). Chạy trong transaction PlaceOrder, chỉ đọc.
 */
interface CheckoutValidator
{
    public function code(): string;

    /**
     * @return list<CheckoutIssue>
     */
    public function validate(CheckoutRequest $request, Totals $totals, bool $cartReady): array;
}
