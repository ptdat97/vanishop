<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

/**
 * Trạng thái khoản hoàn: `processing` (đang hoàn qua cổng) → `completed` | `requested` (chờ nhân viên/kênh chuyển trả);
 * `requested` → `paying_out` (kênh chuyển trả đã giành, chống chuyển hai lần) → `completed` | `requested`.
 */
enum RefundStatus: string
{
    case Requested = 'requested';
    case Processing = 'processing';
    case PayingOut = 'paying_out';
    case Completed = 'completed';

    public static function initial(bool $automatic): self
    {
        return $automatic ? self::Processing : self::Requested;
    }

    /** Nhân viên/kênh ghi nhận đã chuyển trả (nhận cả khoản kênh đã giành mà tiến trình chết trước khi ghi). */
    public function canComplete(): bool
    {
        return in_array($this, [self::Requested, self::PayingOut], true);
    }
}
