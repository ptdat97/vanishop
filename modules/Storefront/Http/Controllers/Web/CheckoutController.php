<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\CheckoutRejected;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Storefront\Application\CheckoutPresenter;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Application\PaymentPresenter;

/**
 * Checkout của native storefront: một form, đặt hàng bằng POST thường (Idempotency-Key là trường ẩn sinh khi
 * render form, `expected_total` là tổng khách đang thấy — ADR-023). Nút "Áp dụng" tính lại mà không đặt hàng.
 */
final class CheckoutController
{
    public function __construct(
        private readonly Checkout $checkout,
        private readonly NativeCart $cart,
        private readonly CheckoutPresenter $presenter,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $key = $this->cart->current() === null ? null : $this->cart->key();
        if ($key === null) {
            return redirect()->route('storefront.cart');
        }

        $old = (array) $request->old();
        $quote = $this->presenter->quote($this->checkout->quote($this->toRequest($old, $request)));

        return view('theme::pages.checkout', [
            'quote' => $quote,
            'idempotencyKey' => (string) Str::ulid(),
        ]);
    }

    public function store(Request $request, Session $session, PaymentPresenter $payments): RedirectResponse
    {
        $data = $request->validate([
            'contact.full_name' => ['nullable', 'string', 'max:120'],
            'contact.phone' => ['nullable', 'string', 'max:20'],
            'contact.email' => ['nullable', 'email', 'max:190'],
            'shipping_address.province_name' => ['nullable', 'string', 'max:120'],
            'shipping_address.ward_name' => ['nullable', 'string', 'max:120'],
            'shipping_address.street_line' => ['nullable', 'string', 'max:255'],
            'shipping_method' => ['nullable', 'string', 'max:64'],
            'payment_method' => ['nullable', 'string', 'max:64'],
            'voucher_code' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:500'],
            'extra' => ['nullable', 'array', 'max:10'],
            'extra.*' => ['array', 'max:20'],
            'extra.*.*' => ['nullable', 'string', 'max:500'],
            'expected_total' => ['required', 'integer', 'min:0'],
            'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,128}$/'],
        ]);

        if ($request->input('action') === 'quote' || $this->cart->current() === null) {
            return redirect()->route('storefront.checkout')->withInput();
        }

        try {
            $result = $this->checkout->placeOrder($this->toRequest($data, $request, (int) $data['expected_total']), (string) $data['idempotency_key']);
        } catch (CheckoutRejected $exception) {
            return redirect()->route('storefront.checkout')->withInput()->withErrors($this->errors($exception));
        }

        $order = $result->body;
        $session->put("vani.orders.{$order['id']}", [
            'token' => $order['access_token'],
            'payment' => $result->payment === null ? null : $payments->present($result->payment),
        ]);
        $this->cart->forget();

        return redirect()->route('storefront.order', $order['id']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function toRequest(array $data, Request $request, ?int $expectedTotal = null): CheckoutRequest
    {
        $address = (array) ($data['shipping_address'] ?? []);
        $voucher = trim((string) ($data['voucher_code'] ?? ''));

        return new CheckoutRequest(
            cart: $this->cart->key(),
            contact: [
                'full_name' => (string) ($data['contact']['full_name'] ?? ''),
                'phone' => (string) ($data['contact']['phone'] ?? ''),
                'email' => ($data['contact']['email'] ?? '') === '' ? null : (string) $data['contact']['email'],
            ],
            // Chưa có danh mục địa giới hành chính: mã lấy từ tên đã chuẩn hoá (thay bằng chọn tỉnh/phường khi có dữ liệu).
            shippingAddress: [
                'province_code' => Str::slug((string) ($address['province_name'] ?? '')),
                'province_name' => (string) ($address['province_name'] ?? ''),
                'ward_code' => Str::slug((string) ($address['ward_name'] ?? '')),
                'ward_name' => (string) ($address['ward_name'] ?? ''),
                'street_line' => (string) ($address['street_line'] ?? ''),
            ],
            shippingMethod: ($data['shipping_method'] ?? null) ?: null,
            paymentMethod: ($data['payment_method'] ?? null) ?: null,
            voucherCodes: $voucher === '' ? [] : [$voucher],
            note: ($data['note'] ?? null) ?: null,
            expectedTotal: $expectedTotal,
            extra: array_filter((array) ($data['extra'] ?? []), fn (mixed $fields, mixed $plugin): bool => is_string($plugin) && is_array($fields), ARRAY_FILTER_USE_BOTH),
            source: (string) $request->attributes->get('order_source', 'web'),
        );
    }

    /**
     * Lỗi checkout → lỗi theo trường của form (trường không xác định → `business`).
     *
     * @return array<string, string>
     */
    private function errors(CheckoutRejected $exception): array
    {
        $errors = [];
        foreach ((array) ($exception->details()['issues'] ?? []) as $issue) {
            $field = $issue['field'] ?? null;
            $field = match (true) {
                $field === null => 'business',
                str_starts_with($field, 'shipping_address.province') => 'shipping_address.province_name',
                str_starts_with($field, 'shipping_address.ward') => 'shipping_address.ward_name',
                default => $field,
            };
            $errors[$field] ??= (string) $issue['message'];
        }

        return $errors === [] ? ['business' => $exception->getMessage()] : $errors;
    }
}
