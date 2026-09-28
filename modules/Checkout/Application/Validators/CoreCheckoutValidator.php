<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Validators;

use Modules\Checkout\Application\PaymentMethods;
use Modules\Checkout\Contracts\CheckoutValidator;
use Modules\Checkout\Contracts\Data\CheckoutIssue;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Shared\Domain\Phone\PhoneNumber;

/**
 * Kiểm tra bắt buộc của Core: giỏ bán được, liên hệ, địa chỉ, giao hàng, thanh toán, một brand mỗi đơn.
 */
final class CoreCheckoutValidator implements CheckoutValidator
{
    public function __construct(private readonly PaymentMethods $payments) {}

    public function code(): string
    {
        return 'core';
    }

    public function validate(CheckoutRequest $request, Totals $totals, bool $cartReady): array
    {
        $issues = [];

        if ($totals->lines === [] || ! $cartReady) {
            $issues[] = new CheckoutIssue('cart_not_ready', __('checkout::messages.cart_not_ready'));
        }
        // Kênh đa brand cần order group (tách đơn theo brand/pháp nhân) — Designed, chưa hỗ trợ.
        if (count($totals->brandIds()) > 1) {
            $issues[] = new CheckoutIssue('multi_brand', __('checkout::messages.multi_brand'));
        }

        $contact = $request->contact ?? [];
        if (trim((string) ($contact['full_name'] ?? '')) === '') {
            $issues[] = new CheckoutIssue('required', __('checkout::messages.name_required'), 'contact.full_name');
        }
        if (PhoneNumber::tryFromString((string) ($contact['phone'] ?? '')) === null) {
            $issues[] = new CheckoutIssue('phone_invalid', __('checkout::messages.phone_invalid'), 'contact.phone');
        }

        foreach (['province_code', 'province_name', 'ward_code', 'ward_name', 'street_line'] as $field) {
            if (trim((string) ($request->shippingAddress[$field] ?? '')) === '') {
                $issues[] = new CheckoutIssue('required', __('checkout::messages.address_required'), "shipping_address.{$field}");
            }
        }

        if ($totals->shipping === null) {
            $issues[] = new CheckoutIssue('shipping_unavailable', __('checkout::messages.shipping_unavailable'), 'shipping_method');
        }

        $methods = array_column($this->payments->available($totals), 'code');
        if ($request->paymentMethod === null || ! in_array($request->paymentMethod, $methods, true)) {
            $issues[] = new CheckoutIssue('payment_unavailable', __('checkout::messages.payment_unavailable'), 'payment_method');
        }

        return $issues;
    }
}
