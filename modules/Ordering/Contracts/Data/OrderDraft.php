<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

/**
 * Mọi số liệu đã được Checkout tính xong (totals pipeline); Ordering chỉ lưu snapshot, không tính lại.
 */
final readonly class OrderDraft
{
    /**
     * @param  list<OrderLineDraft>  $lines
     * @param  list<OrderAdjustmentDraft>  $adjustments
     * @param  array{full_name: string, phone: string, email: ?string}  $customer
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, mixed>  $shippingMethod
     */
    public function __construct(
        public string $publicId,
        /** Nguồn đơn: web | app | zalo | admin | pos | marketplace (báo cáo). */
        public string $source,
        public ?int $customerId,
        public string $currencyCode,
        public string $paymentMethod,
        public string $paymentStatus,
        public array $lines,
        public array $adjustments,
        public int $subtotalAmount,
        public int $discountAmount,
        public int $shippingAmount,
        public int $taxAmount,
        public int $totalAmount,
        public array $customer,
        public array $shippingAddress,
        public array $shippingMethod,
        public ?string $note,
        public string $reservationKey,
        public ?string $sourceCartId,
        /** orders.meta: dữ liệu nhỏ của plugin, khoá theo plugin id (vani.order.before_create). */
        public array $meta = [],
    ) {}
}
