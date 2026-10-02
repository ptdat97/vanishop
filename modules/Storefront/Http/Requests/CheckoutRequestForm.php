<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Storefront\Http\Controllers\Api\CartController;

/**
 * Validate hình thức (kiểu, độ dài). Quy tắc nghiệp vụ (SĐT hợp lệ, phương thức khả dụng…) do Checkout kiểm tra.
 */
final class CheckoutRequestForm extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $placing = $this->routeIs('*.checkout.orders.store');

        return [
            'contact' => [$placing ? 'required' : 'nullable', 'array'],
            'contact.full_name' => ['nullable', 'string', 'max:120'],
            'contact.phone' => ['nullable', 'string', 'max:20'],
            'contact.email' => ['nullable', 'email', 'max:190'],
            'shipping_address' => [$placing ? 'required' : 'nullable', 'array'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:16'],
            'shipping_address.province_name' => ['nullable', 'string', 'max:120'],
            'shipping_address.ward_code' => ['nullable', 'string', 'max:16'],
            'shipping_address.ward_name' => ['nullable', 'string', 'max:120'],
            'shipping_address.street_line' => ['nullable', 'string', 'max:255'],
            'shipping_method' => ['nullable', 'string', 'max:64'],
            'payment_method' => [$placing ? 'required' : 'nullable', 'string', 'max:64'],
            'voucher_codes' => ['array', 'max:5'],
            'voucher_codes.*' => ['string', 'max:64'],
            'note' => ['nullable', 'string', 'max:500'],
            // Trường bổ sung của plugin: {"<plugin id>": {"<field>": scalar}} — tối đa 10 plugin × 20 trường.
            'extra' => ['nullable', 'array', 'max:10'],
            'extra.*' => ['array', 'max:20'],
            'extra.*.*' => ['nullable', 'max:500', fn (string $attribute, mixed $value, \Closure $fail) => is_scalar($value) || $value === null ? null : $fail('Giá trị phải là chuỗi/số/đúng sai.')],
            'expected_total' => [$placing ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    public function toCheckoutRequest(string $cartId): CheckoutRequest
    {
        $contact = $this->validated('contact');
        $address = $this->validated('shipping_address');

        return new CheckoutRequest(
            cart: new CartKey($cartId, (string) $this->headers->get(CartController::TOKEN_HEADER, '')),
            contact: $contact === null ? null : [
                'full_name' => (string) ($contact['full_name'] ?? ''),
                'phone' => (string) ($contact['phone'] ?? ''),
                'email' => isset($contact['email']) ? (string) $contact['email'] : null,
            ],
            shippingAddress: $address === null ? null : array_map('strval', array_intersect_key($address, array_flip(['province_code', 'province_name', 'ward_code', 'ward_name', 'street_line']))),
            shippingMethod: $this->validated('shipping_method'),
            paymentMethod: $this->validated('payment_method'),
            voucherCodes: array_values(array_map('strval', (array) $this->validated('voucher_codes', []))),
            note: $this->validated('note'),
            expectedTotal: $this->validated('expected_total') === null ? null : (int) $this->validated('expected_total'),
            extra: array_filter((array) $this->validated('extra', []), fn (mixed $fields, mixed $plugin): bool => is_string($plugin) && is_array($fields), ARRAY_FILTER_USE_BOTH),
            source: (string) $this->attributes->get('order_source', 'web'),
        );
    }
}
