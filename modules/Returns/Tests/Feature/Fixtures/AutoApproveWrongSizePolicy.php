<?php

declare(strict_types=1);

namespace Modules\Returns\Tests\Feature\Fixtures;

use Modules\Returns\Contracts\Data\ReturnContext;
use Modules\Returns\Contracts\Data\ReturnDecision;
use Modules\Returns\Contracts\ReturnPolicy;

/**
 * Chính sách giả lập của plugin (ADR-046 chốt ③, điểm mở rộng): khách gửi yêu cầu đổi vì sai size → tự duyệt; lý do khác
 * chờ CSKH như mặc định.
 */
final class AutoApproveWrongSizePolicy implements ReturnPolicy
{
    public function code(): string
    {
        return 'test_auto_wrong_size';
    }

    public function evaluate(ReturnContext $context): ReturnDecision
    {
        if ($context->deliveredAt === null) {
            return ReturnDecision::deny('not_delivered');
        }

        return new ReturnDecision(true, autoApprove: $context->source === 'customer' && $context->reasonCode === 'wrong_size');
    }
}
