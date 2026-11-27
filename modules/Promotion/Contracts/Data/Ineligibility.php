<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Promotion\Contracts\ExplainsIneligibility;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Vì sao giỏ chưa đạt một rule ({@see ExplainsIneligibility}): câu cho khách đã dịch, và số
 * tiền còn thiếu nếu rule là ngưỡng tiền ("Mua thêm 120.000đ để dùng mã").
 */
#[PluginApi]
final readonly class Ineligibility
{
    public function __construct(
        public string $message,
        public ?Money $shortfall = null,
    ) {}
}
