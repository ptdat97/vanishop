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
    /** Tag extension point: plugin đóng góp qua `contribute(ReturnPolicy::TAG, …)`. */
    public const TAG = 'vani.returns.policies';

    public function code(): string;

    public function evaluate(ReturnContext $context): ReturnDecision;
}
