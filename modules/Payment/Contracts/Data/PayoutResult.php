<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

/**
 * Kết quả chuyển trả (EP v3 X5): thành công kèm tham chiếu giao dịch, hoặc lỗi kèm thông báo cho nhân viên.
 */
final readonly class PayoutResult
{
    public function __construct(public bool $successful, public ?string $reference = null, public ?string $message = null) {}
}
