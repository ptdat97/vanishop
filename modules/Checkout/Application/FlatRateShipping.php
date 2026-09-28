<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;

/**
 * Phí giao cố định, miễn phí khi tiền hàng (sau giảm giá) đạt ngưỡng. Cấu hình `vanishop.checkout.shipping`.
 */
final class FlatRateShipping implements ShippingRateProvider
{
    public function __construct(
        private readonly int $fee,
        private readonly ?int $freeOver,
    ) {}

    public function options(TotalsContext $context): array
    {
        $free = $this->freeOver !== null && $context->linesTotal()->amount >= $this->freeOver;

        return [new ShippingOption('standard', __('checkout::messages.shipping_standard'), $context->money($free ? 0 : $this->fee))];
    }
}
