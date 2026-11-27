<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Số liệu dùng của một khuyến mãi do plugin quản lý: số đơn đang áp (không tính đơn đã huỷ) và tổng tiền đã giảm.
 */
#[PluginApi]
final readonly class ManagedPromotionUsage
{
    public function __construct(
        public int $promotionId,
        public int $usageCount,
        public int $discountAmount,
    ) {}
}
