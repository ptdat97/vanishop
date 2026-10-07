<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Throwable;

/**
 * Đối soát khoản ĐÃ THU với cổng (khác `vani:payment:reconcile` — khoản chờ, cổng là authority và được áp kết quả).
 * Phát hiện, chỉ ghi lại để xử lý tay:
 * - `gateway_not_captured`: VaniShop đã thu, cổng báo chưa thu/thất bại;
 * - `amount_mismatch`: số tiền cổng báo ≠ số tiền khoản thanh toán;
 * - `refund_mismatch`: tổng hoàn cổng báo ≠ tổng hoàn VaniShop (khi cổng tra được tổng hoàn).
 * Không đổi trạng thái thanh toán/đơn; một lỗi đang mở cho cùng payment + loại không ghi lặp mỗi lần chạy.
 */
final class PaymentVerifier
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly PaymentService $payments,
    ) {}

    /**
     * @return array{id: int, checked: int, skipped: int, issues: int}
     */
    public function verify(int $days = 7): array
    {
        $from = CarbonImmutable::now()->subDays($days);
        $runId = (int) DB::table('payment_reconciliations')->insertGetId(['window_from' => $from, 'started_at' => now()]);
        $checked = $skipped = $issues = 0;

        Payment::query()->whereIn('status', [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded])
            ->where('updated_at', '>=', $from)->orderBy('id')
            ->chunkById(100, function ($batch) use ($runId, &$checked, &$skipped, &$issues): void {
                foreach ($batch as $payment) {
                    $gateway = $this->gateways->get($payment->gateway_code);
                    if ($gateway === null || ! $gateway->capabilities()->query || $gateway->capabilities()->collectsOnDelivery || $gateway->capabilities()->manualConfirmation) {
                        continue; // COD/chuyển khoản tay: không có giao dịch phía cổng để đối chiếu
                    }

                    try {
                        $status = $gateway->query($this->payments->toData($payment));
                    } catch (Throwable $exception) {
                        $skipped++;
                        Log::warning('Không tra cứu được cổng khi đối soát khoản đã thu.', ['payment' => $payment->public_id, 'error' => $exception->getMessage()]);

                        continue;
                    }
                    $checked++;

                    $found = [];
                    if ($status->status !== GatewayCallback::PAID) {
                        $found[] = ['gateway_not_captured', $payment->status->value, $status->status];
                    }
                    if ($status->amount !== null && $status->amount->amount !== (int) $payment->amount) {
                        $found[] = ['amount_mismatch', (string) $payment->amount, (string) $status->amount->amount];
                    }
                    if ($status->refunded !== null && $status->refunded->amount !== (int) $payment->refunded_amount) {
                        $found[] = ['refund_mismatch', (string) $payment->refunded_amount, (string) $status->refunded->amount];
                    }

                    foreach ($found as [$issue, $expected, $actual]) {
                        $open = DB::table('payment_reconciliation_lines')->where('payment_id', $payment->id)->where('issue', $issue)->whereNull('resolved_at')->exists();
                        if ($open) {
                            continue;
                        }
                        DB::table('payment_reconciliation_lines')->insert([
                            'reconciliation_id' => $runId, 'payment_id' => $payment->id, 'gateway_code' => $payment->gateway_code, 'issue' => $issue,
                            'expected' => $expected, 'actual' => $actual, 'detected_at' => now(),
                        ]);
                        $issues++;
                    }
                }
            });

        DB::table('payment_reconciliations')->where('id', $runId)->update(['checked' => $checked, 'skipped' => $skipped, 'issues' => $issues, 'finished_at' => now()]);
        if ($issues > 0) {
            Log::warning('Đối soát thanh toán với cổng có chênh lệch — cần xử lý tay.', ['run_id' => $runId, 'issues' => $issues]);
        }

        return ['id' => $runId, 'checked' => $checked, 'skipped' => $skipped, 'issues' => $issues];
    }
}
