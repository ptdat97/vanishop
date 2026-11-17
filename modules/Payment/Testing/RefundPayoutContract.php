<?php

declare(strict_types=1);

namespace Modules\Payment\Testing;

use Closure;
use Modules\Payment\Contracts\Data\PayoutResult;
use Modules\Payment\Contracts\Data\RefundData;
use Modules\Payment\Contracts\RefundPayout;

/**
 * Contract test cho RefundPayout. Kiểm tra: mã, nhãn; chuyển trả một khoản hoàn mẫu trả PayoutResult — thành công thì có
 * mã tham chiếu, thất bại thì có thông điệp (bên gọi giả lập ngân hàng/cổng).
 */
final class RefundPayoutContract
{
    /**
     * @param  Closure(): RefundPayout  $payout
     * @param  Closure(): RefundData  $refund
     */
    public static function define(string $label, Closure $payout, Closure $refund): void
    {
        describe("RefundPayout contract: {$label}", function () use ($payout, $refund): void {
            it('mã, nhãn; kết quả có tham chiếu hoặc thông điệp', function () use ($payout, $refund): void {
                expect($payout()->code())->toMatch('/^[a-z][a-z0-9_.-]*$/')->and($payout()->label())->not->toBe('');
                $result = $payout()->payout($refund());
                expect($result)->toBeInstanceOf(PayoutResult::class);
                $result->successful
                    ? expect((string) $result->reference)->not->toBe('')
                    : expect((string) $result->message)->not->toBe('');
            });
        });
    }
}
