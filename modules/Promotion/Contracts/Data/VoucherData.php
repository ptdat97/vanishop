<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Promotion\Contracts\Vouchers;
use Modules\Shared\Contracts\PluginApi;

/**
 * Một voucher ({@see Vouchers}). `customerId` khác null = chỉ khách đó dùng được.
 */
#[PluginApi]
final readonly class VoucherData
{
    public function __construct(
        public int $id,
        public int $promotionId,
        public string $code,
        public ?int $customerId,
        public ?int $usageLimit,
        public int $usedCount,
        public ?int $expiresAt,
        public bool $active,
    ) {}
}
