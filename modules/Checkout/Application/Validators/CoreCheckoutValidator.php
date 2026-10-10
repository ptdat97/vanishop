<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Validators;

use Modules\Checkout\Application\Addresses;
use Modules\Checkout\Application\PaymentMethods;
use Modules\Checkout\Contracts\CheckoutValidator;
use Modules\Checkout\Contracts\Data\CheckoutIssue;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Shared\Support\Phones;

/**
 * Kiểm tra bắt buộc của Core: giỏ bán được, liên hệ, địa chỉ, giao hàng, thanh toán.
 */
final class CoreCheckoutValidator implements CheckoutValidator
{
    public function __construct(
        private readonly PaymentMethods $payments,
        private readonly Addresses $addresses,
    ) {}

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

        $contact = $request->contact ?? [];
        if (trim((string) ($contact['full_name'] ?? '')) === '') {
            $issues[] = new CheckoutIssue('required', __('checkout::messages.name_required'), 'contact.full_name');
        }
        if (Phones::parse((string) ($contact['phone'] ?? '')) === null) {
            $issues[] = new CheckoutIssue('phone_invalid', __('checkout::messages.phone_invalid'), 'contact.phone');
        }

        // Có danh mục địa giới: chỉ cần mã tỉnh/phường (tên lấy theo danh mục) và mã phải hợp lệ, khớp nhau.
        $directory = $this->addresses->directory();
        $required = $directory === null ? ['province_code', 'province_name', 'ward_code', 'ward_name', 'street_line'] : ['province_code', 'ward_code', 'street_line'];
        $missing = false;
        foreach ($required as $field) {
            if (trim((string) ($request->shippingAddress[$field] ?? '')) === '') {
                $issues[] = new CheckoutIssue('required', __('checkout::messages.address_required'), "shipping_address.{$field}");
                $missing = true;
            }
        }
        if ($directory !== null && ! $missing && $this->addresses->normalize(array_map('strval', (array) $request->shippingAddress)) === null) {
            $issues[] = new CheckoutIssue('address_invalid', __('checkout::messages.address_invalid'), 'shipping_address.ward_code');
        }

        if ($totals->shipping === null) {
            $issues[] = new CheckoutIssue('shipping_unavailable', __('checkout::messages.shipping_unavailable'), 'shipping_method');
        }

        $methods = array_column($this->payments->available($totals), 'code');
        if ($methods === []) {
            // Không cổng nào khả dụng (plugin thanh toán tắt hết / cấu hình sai): báo rõ thay vì lỗi 500 (ADR-029).
            $issues[] = new CheckoutIssue('no_payment_method', __('checkout::messages.no_payment_method'), 'payment_method');
        } elseif ($request->paymentMethod === null || ! in_array($request->paymentMethod, $methods, true)) {
            $issues[] = new CheckoutIssue('payment_unavailable', __('checkout::messages.payment_unavailable'), 'payment_method');
        }

        return $issues;
    }
}
