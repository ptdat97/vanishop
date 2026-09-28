<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts;

use Modules\Returns\Contracts\Data\ReturnContext;
use Modules\Returns\Contracts\Data\ReturnDecision;

/**
 * Extension point (tag `vani.returns.policies`, chọn bằng `vanishop.returns.policy`). Mặc định `days_window`.
 * Chỉ đọc; Core vẫn luôn kiểm tra số lượng trả ≤ số đã giao.
 */
interface ReturnPolicy
{
    public function code(): string;

    public function evaluate(ReturnContext $context): ReturnDecision;
}
