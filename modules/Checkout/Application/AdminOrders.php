<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutQuote;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Ordering\Contracts\OrderReader;
use RuntimeException;

/**
 * Nhân viên tạo đơn trong Admin (stabilization A8) — đơn nhận qua điện thoại/Zalo/Facebook. Đi đúng đường của checkout
 * storefront: giỏ phía server (mỗi lần tính lại thì đồng bộ dòng), `Checkout::quote` / `placeOrder` với nguồn `admin` — cùng
 * luật giá, khuyến mãi, phí giao, giữ hàng, chống trùng (khoá idempotency do form sinh). Đơn đi tiếp hàng đợi như đơn web.
 */
final class AdminOrders
{
    public const SOURCE = 'admin';

    public function __construct(
        private readonly Carts $carts,
        private readonly Checkout $checkout,
        private readonly OrderReader $orders,
    ) {}

    /**
     * Giỏ của form (tạo khi chưa có) với đúng các dòng `$lines` (variant id => số lượng).
     *
     * @param  array<int, int>  $lines
     */
    public function syncCart(?CartKey $key, array $lines): CartKey
    {
        $key ??= $this->carts->create((string) config('vanishop.currency'))->key;
        $view = $this->carts->view($key);
        if ($view->status !== 'active') {
            return $key; // đã thành đơn (bấm "Tạo đơn" lần nữa) — placeOrder trả lại đơn cũ theo khoá idempotency
        }
        foreach ($view->lines as $line) {
            $this->carts->removeLine($key, $line->id);
        }
        foreach ($lines as $variantId => $quantity) {
            $this->carts->addLine($key, $variantId, $quantity);
        }

        return $key;
    }

    /**
     * @param  array{full_name: string, phone: string, email: ?string}|null  $contact
     * @param  array{province_code: string, province_name: string, ward_code: string, ward_name: string, street_line: string}|null  $address
     */
    public function quote(CartKey $key, ?array $contact, ?array $address, ?string $shippingMethod, ?string $paymentMethod, ?string $note): CheckoutQuote
    {
        return $this->checkout->quote($this->request($key, $contact, $address, $shippingMethod, $paymentMethod, $note, null));
    }

    /**
     * Đặt đơn; trả id đơn (trang đơn Admin). Gửi lại cùng khoá idempotency → cùng đơn, không tạo thêm.
     *
     * @param  array{full_name: string, phone: string, email: ?string}  $contact
     * @param  array{province_code: string, province_name: string, ward_code: string, ward_name: string, street_line: string}  $address
     */
    public function place(CartKey $key, array $contact, array $address, string $shippingMethod, string $paymentMethod, ?string $note, int $expectedTotal, string $idempotencyKey): int
    {
        $result = $this->checkout->placeOrder($this->request($key, $contact, $address, $shippingMethod, $paymentMethod, $note, $expectedTotal), "admin:{$idempotencyKey}");
        $number = (string) ($result->body['number'] ?? '');

        return $this->orders->findByNumber($number)->id ?? throw new RuntimeException("Không tìm thấy đơn vừa tạo {$number}.");
    }

    /**
     * @param  array{full_name: string, phone: string, email: ?string}|null  $contact
     * @param  array{province_code: string, province_name: string, ward_code: string, ward_name: string, street_line: string}|null  $address
     */
    private function request(CartKey $key, ?array $contact, ?array $address, ?string $shippingMethod, ?string $paymentMethod, ?string $note, ?int $expectedTotal): CheckoutRequest
    {
        return new CheckoutRequest(
            cart: $key, contact: $contact, shippingAddress: $address, shippingMethod: $shippingMethod, paymentMethod: $paymentMethod,
            voucherCodes: [], note: $note, expectedTotal: $expectedTotal, source: self::SOURCE,
        );
    }
}
