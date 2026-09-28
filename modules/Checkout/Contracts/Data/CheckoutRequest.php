<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Cart\Contracts\Data\CartKey;

/**
 * Dữ liệu khách nhập ở checkout. Checkout không lưu phiên: mỗi lần quote/đặt hàng gửi đủ dữ liệu.
 */
final readonly class CheckoutRequest
{
    /**
     * @param  array{full_name: string, phone: string, email: ?string}|null  $contact
     * @param  array{province_code: string, province_name: string, ward_code: string, ward_name: string, street_line: string}|null  $shippingAddress
     * @param  list<string>  $voucherCodes
     */
    public function __construct(
        public CartKey $cart,
        public ?array $contact,
        public ?array $shippingAddress,
        public ?string $shippingMethod,
        public ?string $paymentMethod,
        public array $voucherCodes,
        public ?string $note,
        public ?int $expectedTotal,
    ) {}

    /**
     * Băm nội dung để phát hiện cùng Idempotency-Key nhưng khác yêu cầu.
     */
    public function fingerprint(): string
    {
        return hash('sha256', (string) json_encode([
            $this->cart->publicId, $this->contact, $this->shippingAddress, $this->shippingMethod, $this->paymentMethod,
            $this->voucherCodes, $this->note, $this->expectedTotal,
        ], JSON_UNESCAPED_UNICODE));
    }
}
