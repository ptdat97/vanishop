<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Fixtures;

use Modules\Payment\Contracts\Data\PayoutResult;
use Modules\Payment\Contracts\Data\RefundData;
use Modules\Payment\Contracts\RefundPayout;

/** Chi hoàn tiền mẫu: từ chối khoản quá 1.000.000 (chạy RefundPayoutContract). */
final class LimitedPayout implements RefundPayout
{
    public function code(): string
    {
        return 'test-limited';
    }

    public function label(): string
    {
        return 'Chi thử có hạn mức';
    }

    public function payout(RefundData $refund): PayoutResult
    {
        return $refund->amount > 1_000_000
            ? new PayoutResult(false, null, 'vượt hạn mức chi thử')
            : new PayoutResult(true, 'TEST-'.substr($refund->refundPublicId, -6));
    }
}
