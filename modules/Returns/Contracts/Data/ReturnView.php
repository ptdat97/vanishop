<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts\Data;

final readonly class ReturnView
{
    /**
     * @param  list<array{order_line_id: int, sku: string, name: string, quantity: int, refund_amount: int, condition: ?string}>  $lines
     * @param  list<array{from: ?string, to: string, note: ?string, source: string, at: string}>  $events
     */
    public function __construct(
        public int $id,
        public string $publicId,
        public string $number,
        public int $orderId,
        public string $status,
        public string $reasonCode,
        public ?string $customerNote,
        public string $source,
        public int $refundAmount,
        public ?int $refundedAmount,
        public string $currencyCode,
        public string $createdAt,
        public array $lines,
        public array $events,
        public int $lockVersion,
        /** refund | exchange (0.3.32) */
        public string $resolution = 'refund',
        public ?int $replacementOrderId = null,
        /** Mã vận đơn khách gửi hàng về (0.3.74). */
        public ?string $returnTrackingNumber = null,
        /**
         * Ảnh đính kèm (0.3.81): link có chữ ký, hết hạn sau 30 phút.
         *
         * @var list<array{id: int, url: string}>
         */
        public array $attachments = [],
    ) {}
}
