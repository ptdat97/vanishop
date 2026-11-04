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
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\AddressOptions;
use Modules\Storefront\Application\CheckoutPresenter;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Application\PaymentPresenter;
use Modules\Tenancy\Contracts\Settings;

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
        private readonly AddressOptions $addresses,
        private readonly Settings $settings,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $key = $this->cart->current() === null ? null : $this->cart->key();
        if ($key === null) {
            return redirect()->to(LocalizedUrls::route('storefront.cart'));
        }
        if ($this->signInRequired($request)) {
            return $this->toLogin();
        }

        $old = (array) $request->old();
        $quote = $this->presenter->quote($this->checkout->quote($this->toRequest($old, $request)));

        $directory = $this->addresses->directory();
        $province = (string) ($old['shipping_address']['province_code'] ?? '');

        return view('theme::pages.checkout', [
            'quote' => $quote,
            'idempotencyKey' => (string) Str::ulid(),
            // Có danh mục địa giới (plugin): chọn tỉnh/phường; không có: nhập tự do.
            'provinces' => $directory?->provinces(),
            'wards' => $directory === null || $province === '' ? [] : $directory->wards($province),
        ]);
    }

    public function store(Request $request, Session $session, PaymentPresenter $payments): RedirectResponse
    {
        $data = $request->validate([
            'contact.full_name' => ['nullable', 'string', 'max:120'],
            'contact.phone' => ['nullable', 'string', 'max:20'],
            'contact.email' => ['nullable', 'email', 'max:190'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:16'],
            'shipping_address.ward_code' => ['nullable', 'string', 'max:16'],
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

        if ($this->signInRequired($request)) {
            return $this->toLogin();
        }
        if ($request->input('action') === 'quote' || $this->cart->current() === null) {
            return redirect()->to(LocalizedUrls::route('storefront.checkout'))->withInput();
        }

        try {
            $result = $this->checkout->placeOrder($this->toRequest($data, $request, (int) $data['expected_total']), (string) $data['idempotency_key']);
        } catch (CheckoutRejected $exception) {
            return redirect()->to(LocalizedUrls::route('storefront.checkout'))->withInput()->withErrors($this->errors($exception));
        }

        $order = $result->body;
        $session->put("vani.orders.{$order['id']}", [
            'token' => $order['access_token'],
            'payment' => $result->payment === null ? null : $payments->present($result->payment),
        ]);
        // Không quên giỏ trong phiên: dòng chưa chọn còn ở lại (§3.8); giỏ đã đóng thì NativeCart::current() tự bỏ.

        // Cổng chuyển hướng (VNPay…): sang trang cổng ngay; khách quay về qua return URL của cổng → trang đơn.
        $action = $result->payment?->action;
        if ($action !== null && $action->type === PaymentInitiation::REDIRECT && $action->url !== null) {
            return redirect()->away($action->url);
        }

        return redirect()->to(LocalizedUrls::route('storefront.order', $order['id']));
    }

    /**
     * Cửa hàng tắt khách vãng lai (`storefront.guest_checkout`) và khách chưa đăng nhập. Checkout vẫn tự chặn khi đặt
     * hàng; ở đây chỉ để khách không phải điền form rồi mới bị từ chối.
     */
    private function signInRequired(Request $request): bool
    {
        return ! $request->attributes->has(AuthenticateCustomer::ATTRIBUTE)
            && ! (bool) $this->settings->get('core', 'storefront.guest_checkout', true);
    }

    private function toLogin(): RedirectResponse
    {
        return redirect()->guest(LocalizedUrls::route('storefront.account.login'))->with('status', __('storefront::messages.sign_in_to_checkout'));
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
            // Có danh mục địa giới: form gửi mã, Core lấy tên chuẩn. Không có: nhập tên, mã lấy từ tên đã chuẩn hoá.
            shippingAddress: $this->addresses->directory() !== null ? [
                'province_code' => (string) ($address['province_code'] ?? ''),
                'province_name' => '',
                'ward_code' => (string) ($address['ward_code'] ?? ''),
                'ward_name' => '',
                'street_line' => (string) ($address['street_line'] ?? ''),
            ] : [
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
                str_starts_with($field, 'shipping_address.province') => $this->addresses->directory() !== null ? 'shipping_address.province_code' : 'shipping_address.province_name',
                str_starts_with($field, 'shipping_address.ward') => $this->addresses->directory() !== null ? 'shipping_address.ward_code' : 'shipping_address.ward_name',
                default => $field,
            };
            $errors[$field] ??= (string) $issue['message'];
        }

        return $errors === [] ? ['business' => $exception->getMessage()] : $errors;
    }
}
