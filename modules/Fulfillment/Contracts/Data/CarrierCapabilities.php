<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

final readonly class CarrierCapabilities
{
    public function __construct(
        /** Đặt vận đơn tự động qua API (job sau commit). false = nhân viên nhập mã vận đơn. */
        public bool $autoBooking = false,
        public bool $webhooks = false,
        public bool $cancel = false,
    ) {}
}
