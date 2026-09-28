<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class PlacedOrder
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $number,
        public int $brandId,
        public int $legalEntityId,
        public int $channelId,
        public string $orderStatus,
        public string $paymentStatus,
        public int $totalAmount,
        public string $currencyCode,
        /** Token truy cập đơn cho khách — chỉ có lúc tạo, server chỉ lưu hash. */
        public string $accessToken = '',
    ) {}
}
