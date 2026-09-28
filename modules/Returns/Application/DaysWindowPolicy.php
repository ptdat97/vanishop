<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use Modules\Returns\Contracts\Data\ReturnContext;
use Modules\Returns\Contracts\Data\ReturnDecision;
use Modules\Returns\Contracts\ReturnPolicy;

/**
 * Trả được trong N ngày kể từ lần giao gần nhất; nhân viên duyệt. Nhân viên tạo hộ khách thì không bị giới hạn ngày.
 */
final class DaysWindowPolicy implements ReturnPolicy
{
    public function __construct(private readonly int $days) {}

    public function code(): string
    {
        return 'days_window';
    }

    public function evaluate(ReturnContext $context): ReturnDecision
    {
        if ($context->deliveredAt === null) {
            return ReturnDecision::deny('not_delivered');
        }
        if ($context->source === 'customer' && $context->deliveredAt->modify("+{$this->days} days") < $context->now) {
            return ReturnDecision::deny('window_expired');
        }

        return new ReturnDecision(true);
    }
}
