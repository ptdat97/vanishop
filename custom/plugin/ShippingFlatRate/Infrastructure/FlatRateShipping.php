<?php

declare(strict_types=1);

namespace Plugin\ShippingFlatRate\Infrastructure;

use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Tenancy\Contracts\Settings;
use Plugin\ShippingFlatRate\ShippingFlatRateServiceProvider;

/**
 * Phí giao cố định, miễn phí khi tiền hàng (sau giảm giá) đạt ngưỡng. Mã phương thức `standard` giữ nguyên
 * từ khi còn nằm trong Core.
 */
final class FlatRateShipping implements ShippingRateProvider
{
    public function __construct(private readonly Settings $settings) {}

    public function options(TotalsContext $context): array
    {
        $fee = (int) ($this->settings->get(ShippingFlatRateServiceProvider::ID, 'fee') ?? config('vani.shipping-flat-rate.fee', 30_000));
        $freeOver = $this->settings->get(ShippingFlatRateServiceProvider::ID, 'free_over') ?? config('vani.shipping-flat-rate.free_over');
        $free = $freeOver !== null && $context->linesTotal()->amount >= (int) $freeOver;

        return [new ShippingOption('standard', __('vani-shipping-flat-rate::messages.standard'), $context->money($free ? 0 : $fee), ShippingFlatRateServiceProvider::ID)];
    }
}
