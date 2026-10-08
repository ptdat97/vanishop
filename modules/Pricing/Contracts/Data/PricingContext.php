<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

/**
 * `customerId` (0.3.35): khách đang mua — PriceResolver tự tra nhóm khách (CustomerGroupDirectory) để áp giá thành
 * viên khi `customerGroupId` chưa được đặt. Trang công khai cache được không truyền khách (giá chung).
 */
final readonly class PricingContext
{
    public function __construct(
        public int $now,
        public ?int $customerGroupId = null,
        public ?int $customerId = null,
    ) {}
}
