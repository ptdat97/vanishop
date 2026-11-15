<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts;

use Modules\Returns\Contracts\Data\ReturnContext;
use Modules\Returns\Contracts\Data\ReturnDecision;

/**
 * Extension point (tag `vani.returns.policies`, chọn bằng `vanishop.returns.policy`). Mặc định `days_window`: chỉ trả lời
 * được gửi hay không — mọi yêu cầu chờ CSKH duyệt (ADR-046 chốt ③). Chính sách của plugin được tự duyệt ca đủ điều kiện
 * (`ReturnDecision::$autoApprove`). Chỉ đọc; Core vẫn luôn kiểm tra số lượng trả ≤ số đã giao.
 */
interface ReturnPolicy
{
    /** Tag extension point: plugin đóng góp qua `contribute(ReturnPolicy::TAG, …)`. */
    public const TAG = 'vani.returns.policies';

    public function code(): string;

    public function evaluate(ReturnContext $context): ReturnDecision;
}
