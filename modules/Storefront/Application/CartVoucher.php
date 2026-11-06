<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Contracts\Session\Session;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Mã giảm giá nhập ở giỏ (giỏ mini / trang giỏ) của native storefront. Giỏ không lưu mã (checkout không trạng thái,
 * ADR-023): mã giữ trong phiên web, checkout điền sẵn, đặt hàng vẫn tính lại như mọi lần. Xem trước mức giảm bằng báo giá
 * checkout của các dòng đã chọn — chưa có địa chỉ nên chưa tính phí giao.
 */
final class CartVoucher
{
    public const SESSION_KEY = 'vani.voucher_code';

    public function __construct(
        private readonly Session $session,
        private readonly Checkout $checkout,
        private readonly MoneyFormatter $money,
    ) {}

    public function code(): ?string
    {
        $code = $this->session->get(self::SESSION_KEY);

        return is_string($code) && $code !== '' ? $code : null;
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Kiểm tra mã trên giỏ; hợp lệ thì giữ trong phiên. Trả lý do từ chối (null = đã áp dụng).
     */
    public function apply(CartKey $cart, string $code): ?string
    {
        $code = trim($code);
        $rejection = $this->quote($cart, $code)['rejection'];
        if ($rejection === null) {
            $this->session->put(self::SESSION_KEY, $code);
        }

        return $rejection;
    }

    /**
     * Mã đang giữ và mức giảm hiện tại; null khi chưa nhập mã. Giỏ đổi làm mã hết hợp lệ (vd. dưới mức tối thiểu): vẫn
     * giữ mã, báo lý do để khách biết.
     *
     * @return array{code: string, discount: array<string, mixed>, total: array<string, mixed>, rejection: string|null}|null
     */
    public function preview(CartKey $cart): ?array
    {
        $code = $this->code();

        return $code === null ? null : ['code' => $code, ...$this->quote($cart, $code)];
    }

    /**
     * Câu cho khách theo lý do từ chối (`VoucherRejection::$reason`); lý do lạ → "không áp dụng được".
     */
    public static function message(string $reason, string $code): string
    {
        $key = "storefront::messages.voucher_rejections.{$reason}";

        return trans()->has($key) ? __($key, ['code' => $code]) : __('storefront::messages.voucher_rejections.not_applicable', ['code' => $code]);
    }

    /**
     * @return array{discount: array<string, mixed>, total: array<string, mixed>, rejection: string|null}
     */
    private function quote(CartKey $cart, string $code): array
    {
        $totals = $this->checkout->quote(new CheckoutRequest(
            cart: $cart, contact: null, shippingAddress: null, shippingMethod: null, paymentMethod: null,
            voucherCodes: [$code], note: null, expectedTotal: null,
        ))->totals;
        $rejection = collect($totals->rejectedVouchers)->first(fn (VoucherRejection $rejection): bool => strcasecmp($rejection->code, $code) === 0);

        return [
            'discount' => $this->money->toArray($totals->discount),
            // Tạm tính sau giảm, chưa gồm phí giao.
            'total' => $this->money->toArray($totals->subtotal->subtract($totals->discount)),
            'rejection' => $rejection === null ? null : self::message($rejection->reason, $code),
        ];
    }
}
