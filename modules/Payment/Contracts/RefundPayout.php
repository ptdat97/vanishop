<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\PayoutResult;
use Modules\Payment\Contracts\Data\RefundData;

/**
 * Extension point (tag `vani.payment.refund_payouts`, EP v3 X5): chuyển trả tiền cho khoản hoàn "chờ chuyển trả" (COD,
 * chuyển khoản — cổng không tự hoàn) qua API ngân hàng/ví thay cho CSKH tự chuyển. CSKH bấm "Chuyển trả qua …"; Core gọi
 * **ngoài** transaction; thành công → ghi nhận đã chuyển trả với tham chiếu, lỗi → giữ "chờ chuyển trả" và báo lỗi.
 */
interface RefundPayout
{
    public const TAG = 'vani.payment.refund_payouts';

    public function code(): string;

    public function label(): string;

    public function payout(RefundData $refund): PayoutResult;
}
