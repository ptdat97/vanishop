<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Calculators;

use Modules\Checkout\Application\ShippingOptions;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TotalsCalculator;

/**
 * Chọn phương thức giao theo mã khách gửi (không gửi → phương thức đầu tiên). Mã không hợp lệ → không có
 * phí giao, validator `shipping` sẽ chặn đặt hàng.
 */
final class ShippingCalculator implements TotalsCalculator
{
    public function __construct(private readonly ShippingOptions $options) {}

    public function code(): string
    {
        return 'shipping';
    }

    public function priority(): int
    {
        return 500;
    }

    public function calculate(TotalsContext $context): TotalsContext
    {
        $options = $this->options->for($context);
        $selected = $context->shippingMethod === null
            ? ($options[0] ?? null)
            : (array_values(array_filter($options, fn ($option): bool => $option->code === $context->shippingMethod))[0] ?? null);

        return $context->withShipping($selected);
    }
}
