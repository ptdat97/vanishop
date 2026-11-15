<?php

declare(strict_types=1);

namespace Plugin\HelloWorld\Infrastructure;

use Modules\Payment\Contracts\Data\PayoutResult;
use Modules\Payment\Contracts\Data\RefundData;
use Modules\Payment\Contracts\RefundPayout;

/**
 * Kênh chuyển trả mẫu (EP v3 X5 — tham chiếu cho `RefundPayout`): không chuyển tiền thật; khoản trên 1.000.000đ bị từ chối
 * để minh hoạ đường lỗi. Plugin thật gọi API ngân hàng/ví.
 */
final class HelloPayout implements RefundPayout
{
    public function code(): string
    {
        return 'hello';
    }

    public function label(): string
    {
        return 'Hello (thử)';
    }

    public function payout(RefundData $refund): PayoutResult
    {
        return $refund->amount > 1_000_000
            ? new PayoutResult(false, null, 'vượt hạn mức chuyển thử')
            : new PayoutResult(true, 'HELLO-'.substr($refund->refundPublicId, -6));
    }
}
