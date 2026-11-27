<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Hết suất giá ngay lúc ghi nhận (đơn khác vừa mua hết) → đơn rollback; khách xem lại giỏ (giá về giá thường).
 */
final class PriceQuotaExceeded extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct(__('pricing::messages.quota_exceeded'));
    }

    public function errorCode(): string
    {
        return 'pricing.quota_exceeded';
    }

    public function status(): int
    {
        return 409;
    }
}
