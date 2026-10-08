<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class OrderData
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $number,
        public ?int $customerId,
        public OrderStatus $status,
        public string $paymentStatus,
        public string $paymentMethod,
        public int $totalAmount,
        public string $currencyCode,
        public string $reservationKey,
        public string $returnStatus = 'none',
        /** @var array{full_name: string, phone: string, email?: ?string} */
        public array $recipient = ['full_name' => '', 'phone' => ''],
        /** @var array<string, string> */
        public array $shippingAddress = [],
        public string $fulfillmentStatus = 'unfulfilled',
        /** ISO-8601 */
        public ?string $placedAt = null,
        /** ISO-8601, độ chính xác giây — dùng làm con trỏ đồng bộ. */
        public ?string $updatedAt = null,
        /** orders.meta (dữ liệu của plugin, khoá theo plugin id). */
        public array $meta = [],
        public string $source = 'web',
        /**
         * Phương thức giao khách chọn (snapshot lúc đặt): `source` = mã carrier tương ứng (vd. `ghn`) hoặc plugin
         * báo phí không gắn hãng (vd. `vani.shipping-flat-rate`).
         *
         * @var array{code?: ?string, label?: ?string, source?: ?string, fee?: int}
         */
        public array $shippingMethod = [],
        /** Đơn gốc nếu đây là đơn thay thế (đổi hàng, 0.3.32). */
        public ?int $parentOrderId = null,
    ) {}
}
