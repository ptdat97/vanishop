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
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\AddressOptions;
use Modules\Storefront\Application\CartVoucher;
use Modules\Storefront\Application\CheckoutPresenter;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Application\PaymentPresenter;
use Modules\Storefront\Http\Requests\CheckoutRequestForm;

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
        private readonly CartVoucher $voucher,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $key = $this->cart->current() === null ? null : $this->cart->key();
        if ($key === null) {
            return redirect()->to(LocalizedUrls::route('storefront.cart'));
        }

        $old = (array) $request->old();
        // Mã của giỏ (ADR-044): mã hỏng tự bỏ khỏi giỏ, không phải lỗi. Mã khách vừa gõ ở đây đã được áp vào giỏ khi gửi form
        // (store); bị từ chối thì một dòng nhắc nhẹ kèm lý do (flash `voucher_notice`).
        $voucherCode = $this->voucher->preview($key)['code'] ?? '';
        $checkoutQuote = $this->checkout->quote(CheckoutRequestForm::build($old, $key, (string) $request->attributes->get('order_source', 'web')));
        $quote = $this->presenter->quote($checkoutQuote);
        $voucherNotice = $request->session()->get('voucher_notice');
        $quote['rejected_vouchers'] = [];

        $directory = $this->addresses->directory();
        $province = (string) ($old['shipping_address']['province_code'] ?? '');

        return view('theme::pages.checkout', [
            'quote' => $quote,
            'voucherCode' => $voucherCode,
            'voucherNotice' => $voucherNotice,
            'idempotencyKey' => (string) Str::ulid(),
            // Có danh mục địa giới (plugin): chọn tỉnh/phường; không có: nhập tự do.
            'provinces' => $directory?->provinces(),
            'wards' => $directory === null || $province === '' ? [] : $directory->wards($province),
        ]);
    }

    public function store(CheckoutRequestForm $request, Session $session, PaymentPresenter $payments): RedirectResponse
    {
        // Cùng hợp đồng đầu vào với Storefront API (ADR-044): luật validate và cách dựng CheckoutRequest ở CheckoutRequestForm.
        $data = $request->validated();

        if ($this->cart->current() === null) {
            return redirect()->to(LocalizedUrls::route('storefront.checkout'))->withInput();
        }

        // Ô mã giảm giá ghi vào giỏ như ô mã ở trang giỏ (một mô hình, ADR-044). Mã không dùng được không lưu: "Áp dụng" →
        // nhắc nhẹ; "Đặt hàng" → đơn đặt không kèm mã (Checkout bỏ mã hỏng cho mọi kênh, §6).
        $typed = trim((string) ($data['voucher_code'] ?? ''));
        $notice = null;
        if ($typed === '') {
            $this->voucher->forget($this->cart->key());
        } elseif (strcasecmp($typed, (string) $this->voucher->code($this->cart->key())) !== 0) {
            $notice = $this->voucher->apply($this->cart->key(), $typed);
        }
        if ($request->input('action') === 'quote') {
            return redirect()->to(LocalizedUrls::route('storefront.checkout'))->withInput($request->except('voucher_code'))->with('voucher_notice', $notice);
        }

        try {
            $result = $this->checkout->placeOrder($request->toCheckoutRequest($this->cart->key()), (string) $data['idempotency_key']);
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
