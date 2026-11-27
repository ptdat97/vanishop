<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Promotion\Contracts\ExplainsIneligibility;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Mã voucher không áp được. reason: not_found | expired | exhausted | not_active | not_applicable | customer_limit (khách đã
 * dùng hết lượt của mình, 0.5.109) | not_owner (mã gán cho khách khác hoặc cần đăng nhập, 0.5.109).
 * `hint`/`shortfall` (0.5.109): rule chưa đạt giải thích được ({@see ExplainsIneligibility}).
 */
#[PluginApi]
final readonly class VoucherRejection
{
    public function __construct(
        public string $code,
        public string $reason,
        public ?string $hint = null,
        public ?Money $shortfall = null,
    ) {}
}
