<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class OrderData
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $number,
        public int $legalEntityId,
        public int $brandId,
        public int $channelId,
        public ?int $customerId,
        public OrderStatus $status,
        public string $paymentStatus,
        public string $paymentMethod,
        public int $totalAmount,
        public string $currencyCode,
        public string $reservationKey,
    ) {}
}
