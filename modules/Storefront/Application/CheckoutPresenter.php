<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Checkout\Contracts\Data\Adjustment;
use Modules\Checkout\Contracts\Data\CheckoutQuote;
use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Shared\Support\MoneyFormatter;

final class CheckoutPresenter
{
    public function __construct(private readonly MoneyFormatter $money) {}

    /**
     * @return array<string, mixed>
     */
    public function quote(CheckoutQuote $quote): array
    {
        $totals = $quote->totals;

        return [
            'currency' => $totals->currencyCode,
            'cart_ready' => $quote->cartReady,
            'lines' => array_map(fn (TotalsLine $line): array => [
                'variant_id' => $line->variantId,
                'sku' => $line->sku,
                'name' => $line->name,
                'quantity' => $line->quantity,
                'unit_price' => $this->money->toArray($line->unitPrice),
                'subtotal' => $this->money->toArray($line->subtotal),
                'discount' => $this->money->toArray($line->discount),
                'total' => $this->money->toArray($line->total()),
                'options' => $line->options,
            ], $totals->lines),
            'adjustments' => array_map(fn (Adjustment $adjustment): array => [
                'type' => $adjustment->type,
                'code' => $adjustment->code,
                'label' => $adjustment->label,
                'amount' => $this->money->toArray($adjustment->amount),
            ], $totals->adjustments),
            'rejected_vouchers' => array_map(fn (VoucherRejection $rejection): array => ['code' => $rejection->code, 'reason' => $rejection->reason], $totals->rejectedVouchers),
            'shipping' => $totals->shipping === null ? null : $this->option($totals->shipping),
            'shipping_options' => array_map($this->option(...), $quote->shippingOptions),
            'payment_methods' => $quote->paymentMethods,
            'subtotal' => $this->money->toArray($totals->subtotal),
            'discount' => $this->money->toArray($totals->discount),
            'shipping_fee' => $this->money->toArray($totals->shippingFee()),
            'tax_included' => $this->money->toArray($totals->tax),
            'total' => $this->money->toArray($totals->grandTotal),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function option(ShippingOption $option): array
    {
        return ['code' => $option->code, 'label' => $option->label, 'fee' => $this->money->toArray($option->fee)];
    }
}
