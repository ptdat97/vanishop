<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Mã giảm giá của giỏ — một mô hình cho mọi kênh (ADR-044 §5): mã lưu trên giỏ (`Carts::setVoucherCodes`), Storefront API
 * `PUT /carts/{id}/vouchers` và ô mã của Blade cùng đi qua đây. Chỉ lưu mã đang dùng được; mã bị từ chối trả lý do. Checkout
 * tính trên mã của giỏ; mã hỏng sau đó bị bỏ khi đặt hàng. Xem trước mức giảm bằng báo giá checkout của các dòng đã chọn —
 * chưa có địa chỉ nên chưa tính phí giao.
 */
final class CartVoucher
{
    public function __construct(
        private readonly Carts $carts,
        private readonly Checkout $checkout,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * Mã đầu tiên của giỏ — Blade hiện một ô mã (SME).
     */
    public function code(CartKey $cart): ?string
    {
        return $this->carts->view($cart)->voucherCodes[0] ?? null;
    }

    public function forget(CartKey $cart): void
    {
        $this->carts->setVoucherCodes($cart, []);
    }

    /**
     * Đặt danh sách mã của giỏ: tính thử với đúng các mã này, chỉ lưu mã dùng được.
     *
     * @param  list<string>  $codes
     * @return array{cart: CartView, rejected: list<VoucherRejection>}
     */
    public function applyCodes(CartKey $cart, array $codes): array
    {
        $codes = array_values(array_filter(array_map(fn (mixed $code): string => trim((string) $code), $codes), fn (string $code): bool => $code !== ''));
        $rejected = $codes === [] ? [] : $this->totals($cart, $codes)->rejectedVouchers;
        $accepted = array_values(array_filter($codes, fn (string $code): bool => self::find($rejected, $code) === null));

        return ['cart' => $this->carts->setVoucherCodes($cart, $accepted), 'rejected' => $rejected];
    }

    /**
     * Ô mã của Blade: thay mã của giỏ bằng một mã. Trả lý do từ chối (null = đã áp dụng).
     */
    public function apply(CartKey $cart, string $code): ?string
    {
        $code = trim($code);
        $rejection = self::find($this->applyCodes($cart, [$code])['rejected'], $code);

        return $rejection === null ? null : self::message($rejection->reason, $code);
    }

    /**
     * Mã đầu tiên đang gắn với giỏ và mức giảm hiện tại; null khi chưa có mã. Mã không còn dùng được (giỏ đổi dưới mức tối
     * thiểu, mã hết hạn/hết lượt…) thì tự bỏ khỏi giỏ, không báo lỗi.
     *
     * @return array{code: string, discount: array<string, mixed>, total: array<string, mixed>, rejection: null}|null
     */
    public function preview(CartKey $cart): ?array
    {
        $codes = $this->carts->view($cart)->voucherCodes;
        if ($codes === []) {
            return null;
        }
        $totals = $this->totals($cart, null);
        if ($totals->rejectedVouchers !== []) {
            $codes = array_values(array_filter($codes, fn (string $code): bool => self::find($totals->rejectedVouchers, $code) === null));
            $this->carts->setVoucherCodes($cart, $codes);
            if ($codes === []) {
                return null;
            }
        }

        return [
            'code' => $codes[0],
            'discount' => $this->money->toArray($totals->discount),
            // Tạm tính sau giảm, chưa gồm phí giao.
            'total' => $this->money->toArray($totals->subtotal->subtract($totals->discount)),
            'rejection' => null,
        ];
    }

    /**
     * Lý do từ chối mã trong một báo giá checkout (null = áp dụng được / không có mã đó).
     *
     * @param  list<VoucherRejection>  $rejections
     */
    public static function rejectionFor(array $rejections, string $code): ?string
    {
        $rejection = self::find($rejections, $code);

        return $rejection === null ? null : self::message($rejection->reason, $code);
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
     * @param  list<VoucherRejection>  $rejections
     */
    private static function find(array $rejections, string $code): ?VoucherRejection
    {
        foreach ($rejections as $rejection) {
            if (strcasecmp($rejection->code, $code) === 0) {
                return $rejection;
            }
        }

        return null;
    }

    /**
     * @param  list<string>|null  $codes  null = mã đang lưu trên giỏ
     */
    private function totals(CartKey $cart, ?array $codes): Totals
    {
        return $this->checkout->quote(new CheckoutRequest(
            cart: $cart, contact: null, shippingAddress: null, shippingMethod: null, paymentMethod: null,
            voucherCodes: $codes, note: null, expectedTotal: null,
        ))->totals;
    }
}
