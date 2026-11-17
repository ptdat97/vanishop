<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
final readonly class ReturnDecision
{
    public function __construct(
        public bool $eligible,
        public ?string $reason = null,
        /**
         * Điểm mở rộng chốt ③ (ADR-046): duyệt ngay không chờ CSKH — chuyển `approved` qua cùng lệnh, nguồn `system`, lý do
         * `auto_approved`. Chính sách mặc định `days_window` không dùng; nhận hàng, kiểm tình trạng, hoàn tiền vẫn là CSKH.
         */
        public bool $autoApprove = false,
    ) {}

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
