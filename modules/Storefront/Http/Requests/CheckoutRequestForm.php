<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Data\CheckoutRequest;

/**
 * Hợp đồng đầu vào DUY NHẤT của checkout cho mọi kênh (ADR-044 §2): Storefront API (`quote`, `orders`) và form checkout
 * của Blade dùng cùng luật validate và cùng cách dựng `CheckoutRequest`. Chỉ validate hình thức (kiểu, độ dài); quy tắc
 * nghiệp vụ (SĐT hợp lệ, phương thức khả dụng, địa chỉ có trong danh mục…) do Checkout kiểm tra.
 *
 * Khác nhau giữa hai kênh chỉ ở phần vận chuyển, không ở luật: Blade gửi `idempotency_key` trong form (API: header),
 * `action=quote` cho nút "Áp dụng", và một ô `voucher_code` (ghi vào giỏ — mã giảm giá thuộc giỏ, không thuộc checkout).
 */
final class CheckoutRequestForm extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $placing = $this->placing();
        $rules = [
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
            'note' => ['nullable', 'string', 'max:500'],
            // Trường bổ sung của plugin: {"<plugin id>": {"<field>": scalar}} — tối đa 10 plugin × 20 trường.
            'extra' => ['nullable', 'array', 'max:10'],
            'extra.*' => ['array', 'max:20'],
            'extra.*.*' => ['nullable', 'max:500', fn (string $attribute, mixed $value, Closure $fail) => is_scalar($value) || $value === null ? null : $fail('Giá trị phải là chuỗi/số/đúng sai.')],
            'expected_total' => [$placing ? 'required' : 'nullable', 'integer', 'min:0'],
        ];

        if ($this->fromBlade()) {
            $rules += [
                'action' => ['nullable', 'in:quote'],
                'voucher_code' => ['nullable', 'string', 'max:64'],
                'idempotency_key' => [$placing ? 'required' : 'nullable', 'string', 'regex:/^[A-Za-z0-9_-]{8,128}$/'],
            ];
        }

        return $rules;
    }

    /** Form checkout của Blade (`POST /thanh-toan`), không phải Storefront API. */
    public function fromBlade(): bool
    {
        return $this->routeIs('storefront.checkout.store', 'storefront.*.checkout.store');
    }

    /** Đặt hàng (không phải tính tổng): API `POST /checkout/{cart}/orders`, hoặc form Blade không bấm "Áp dụng". */
    public function placing(): bool
    {
        return $this->routeIs('*.checkout.orders.store') || ($this->fromBlade() && $this->input('action') !== 'quote');
    }

    public function toCheckoutRequest(CartKey $cart): CheckoutRequest
    {
        return self::build($this->validated(), $cart, (string) $this->attributes->get('order_source', 'web'));
    }

    /**
     * Dữ liệu checkout (đã validate, hoặc dữ liệu cũ của form Blade) → `CheckoutRequest`. Địa chỉ: thiếu mã tỉnh/phường
     * nhưng có tên (cửa hàng không có danh mục địa giới, nhập tự do) → mã lấy từ tên đã chuẩn hoá; có danh mục thì Checkout
     * lấy tên chuẩn theo mã. Mã giảm giá lấy từ giỏ (ADR-044).
     *
     * @param  array<string, mixed>  $data
     */
    public static function build(array $data, CartKey $cart, string $source = 'web'): CheckoutRequest
    {
        $contact = is_array($data['contact'] ?? null) ? $data['contact'] : null;
        $address = is_array($data['shipping_address'] ?? null) ? $data['shipping_address'] : null;
        $text = fn (array $values, string $key): string => trim((string) ($values[$key] ?? ''));
        $code = fn (array $values, string $level): string => $text($values, "{$level}_code") !== '' ? $text($values, "{$level}_code") : Str::slug($text($values, "{$level}_name"));

        return new CheckoutRequest(
            cart: $cart,
            contact: $contact === null ? null : [
                'full_name' => $text($contact, 'full_name'),
                'phone' => $text($contact, 'phone'),
                'email' => $text($contact, 'email') === '' ? null : $text($contact, 'email'),
            ],
            shippingAddress: $address === null ? null : [
                'province_code' => $code($address, 'province'),
                'province_name' => $text($address, 'province_name'),
                'ward_code' => $code($address, 'ward'),
                'ward_name' => $text($address, 'ward_name'),
                'street_line' => $text($address, 'street_line'),
            ],
            shippingMethod: ($data['shipping_method'] ?? null) ?: null,
            paymentMethod: ($data['payment_method'] ?? null) ?: null,
            voucherCodes: null,
            note: ($data['note'] ?? null) ?: null,
            expectedTotal: isset($data['expected_total']) && $data['expected_total'] !== '' ? (int) $data['expected_total'] : null,
            extra: array_filter((array) ($data['extra'] ?? []), fn (mixed $fields, mixed $plugin): bool => is_string($plugin) && is_array($fields), ARRAY_FILTER_USE_BOTH),
            source: $source,
        );
    }
}
