<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Payment\Contracts\Data\PayoutResult;
use Modules\Payment\Contracts\Data\RefundData;
use Modules\Payment\Contracts\PaymentRejected;
use Modules\Payment\Contracts\RefundPayout;
use Modules\Payment\Contracts\RefundsPayments;
use Modules\Payment\Domain\CodRules;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Domain\RefundDecision;
use Modules\Payment\Domain\RefundRules;
use Modules\Payment\Domain\RefundStatus;
use Modules\Payment\Events\RefundCompleted;
use Modules\Payment\Events\RefundCreated;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\ExternalCalls;
use Throwable;

/**
 * Hoàn tiền (docs/10-payment/payment.md §3): quyết định theo `RefundRules` của Domain (không vượt số đã thu, chỉ khoản đang
 * giữ tiền); hoàn qua cổng sau commit, không được thì chờ nhân viên hoặc kênh chuyển trả của plugin (EP v3 X5).
 */
final class RefundService
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly AuditLogger $audit,
        private readonly CurrentContext $context,
        private readonly Extensions $extensions,
        private readonly PaymentRecords $records,
    ) {}

    /**
     * Hoàn tiền do nhân viên tạo. Hook chặn `vani.payment.before_refund` (EP v3 X2) — hoàn tự động khi huỷ đơn/IPN muộn
     * không qua hook này.
     */
    public function refund(int $paymentId, Money $amount, string $reason, string $idempotencyKey): void
    {
        $payment = Payment::query()->findOrFail($paymentId);
        $blocked = array_values(array_filter(Hook::collect('vani.payment.before_refund', $this->records->toView($payment), $amount, $reason), fn ($message): bool => is_string($message) && $message !== ''));
        if ($blocked !== []) {
            throw PaymentRejected::refundBlocked($blocked);
        }
        $this->create($paymentId, $amount, $reason, $idempotencyKey);
    }

    /**
     * Hoàn toàn bộ phần còn hoàn được, tính trong khoá payment: không còn gì (đã hoàn bởi luồng khác) hoặc chưa thu tiền →
     * không làm gì, không lỗi.
     */
    public function refundRemaining(int $paymentId, string $reason, string $idempotencyKey): void
    {
        $this->create($paymentId, null, $reason, $idempotencyKey);
    }

    public function refundOrder(int $orderId, Money $amount, string $reason, string $idempotencyKey): void
    {
        $payment = Payment::query()->where('order_id', $orderId)->whereIn('status', [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded])
            ->orderByDesc('id')->get()
            ->first(fn (Payment $payment): bool => RefundRules::refundable($payment->amount, $payment->refunded_amount) >= $amount->amount);

        if ($payment === null) {
            throw PaymentRejected::refundExceeds((int) Payment::query()->where('order_id', $orderId)->get()
                ->sum(fn (Payment $payment): int => $payment->status->hasCollected() ? RefundRules::refundable($payment->amount, $payment->refunded_amount) : 0));
        }

        $this->refund($payment->id, $amount, $reason, $idempotencyKey);
    }

    /**
     * Huỷ một phần đơn: COD chờ thu → giảm số tiền cần thu (vận đơn tạo lại mang số mới); đã thu → hoàn phần huỷ
     * (idempotent theo lần huỷ). Thanh toán online chưa trả không được huỷ một phần (OrderPolicy).
     */
    public function settlePartialCancellation(int $orderId, int $amount, string $cancellationId, string $reason): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($orderId, $amount, $cancellationId, $reason): void {
            $payments = Payment::query()->where('order_id', $orderId)->lockForUpdate()->get();
            $cod = $payments->first(fn (Payment $payment): bool => $payment->status === PaymentStatus::Pending && $this->records->collectsOnDelivery($payment->gateway_code));
            if ($cod !== null) {
                $cod->update(['amount' => CodRules::amountAfterPartialCancellation((int) $cod->amount, $amount), 'lock_version' => $cod->lock_version + 1]);
                $this->audit->record('payment.amount_reduced', 'payment', $cod->id, ['by' => $amount, 'cancellation' => $cancellationId]);

                return;
            }

            if ($payments->contains(fn (Payment $payment): bool => $payment->status->hasCollected())) {
                $this->refundOrder($orderId, Money::of($amount, (string) $payments->first()->currency_code), "order_lines_cancelled:{$reason}", "lines-cancel:{$cancellationId}");
            }
        });
    }

    /**
     * @return array<string, RefundPayout>
     */
    public function payouts(): array
    {
        return $this->extensions->implementations(RefundPayout::TAG, RefundPayout::class);
    }

    /**
     * Chuyển trả khoản hoàn "chờ chuyển trả" qua plugin (EP v3 X5): gọi ngoài transaction; thành công → ghi nhận đã chuyển
     * trả với tham chiếu; lỗi → trả về "chờ chuyển trả", ném PaymentRejected với thông báo của plugin. Trả tham chiếu giao dịch.
     *
     * Trước khi gọi kênh, khoản hoàn được **giành** bằng UPDATE có điều kiện `requested → paying_out`: hai lần bấm cùng lúc
     * chỉ một lần gọi kênh — không chuyển tiền hai lần. Tiến trình chết giữa chừng → khoản hoàn nằm ở `paying_out` (hiện
     * "Đang chuyển trả"): nhân viên kiểm tra ngân hàng rồi bấm "Đã chuyển trả".
     */
    public function payoutRefund(int $refundId, string $channel): string
    {
        $payout = $this->payouts()[$channel] ?? throw PaymentRejected::notFound();
        $refund = Refund::query()->whereKey($refundId)->firstOrFail();
        if ($this->claim($refundId, RefundStatus::Requested, RefundStatus::PayingOut) === 0) {
            throw PaymentRejected::invalidState((string) Refund::query()->whereKey($refundId)->value('status'));
        }
        $payment = Payment::query()->whereKey($refund->payment_id)->firstOrFail();
        $order = $this->orders->find($payment->order_id);
        $result = new PayoutResult(false, null, null);
        try {
            ExternalCalls::guard("payment.refund_payout:{$channel}");
            $result = $this->extensions->call($payout, fn () => $payout->payout(new RefundData(
                (string) $refund->public_id, (string) $payment->public_id, (string) $order?->number, (int) $refund->amount, (string) $payment->currency_code, (string) $refund->reason,
                ['full_name' => (string) ($order?->recipient['full_name'] ?? ''), 'phone' => (string) ($order?->recipient['phone'] ?? '')],
            )), $result, 'payment.refund_payout');
        } finally {
            if (! $result->successful) {
                $this->claim($refundId, RefundStatus::PayingOut, RefundStatus::Requested);
            }
        }
        if (! $result->successful) {
            throw PaymentRejected::payoutFailed($payout->label(), (string) ($result->message ?? ''));
        }
        $this->completeManualRefund($refund->id, __('payment::texts.refund_service.pay_out_via_label', ['label' => $payout->label()]).($result->reference === null ? '' : ": {$result->reference}"));

        return (string) $result->reference;
    }

    /**
     * Nhân viên đã chuyển trả tiền (hoàn thủ công: COD, chuyển khoản), hoặc kênh chuyển trả báo thành công.
     */
    public function completeManualRefund(int $refundId, string $note): void
    {
        DB::transaction(function () use ($refundId, $note): void {
            $refund = Refund::query()->whereKey($refundId)->lockForUpdate()->firstOrFail();
            if (! RefundStatus::from((string) $refund->status)->canComplete()) {
                return;
            }

            $refund->update(['status' => RefundStatus::Completed->value]);
            $payment = Payment::query()->whereKey($refund->payment_id)->firstOrFail();
            $this->audit->record('payment.refund_completed', 'refund', $refund->id, ['amount' => $refund->amount, 'note' => $note]);
            event(new RefundCompleted($refund->id, $payment->id, $payment->order_id, (int) $refund->amount, $refund->public_id, $payment->public_id));
        });
    }

    /**
     * @param  Money|null  $amount  null = phần còn hoàn được (0 hoặc chưa thu → bỏ qua)
     */
    private function create(int $paymentId, ?Money $amount, string $reason, string $idempotencyKey): void
    {
        if (Refund::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }

        [$refund, $payment] = DB::transaction(function () use ($paymentId, $amount, $reason, $idempotencyKey): array {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            if (Refund::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return [null, $payment];
            }
            $decision = RefundRules::decide($payment->status, (int) $payment->amount, (int) $payment->refunded_amount, $amount?->amount);
            match ($decision->outcome) {
                RefundDecision::NOT_COLLECTED => throw PaymentRejected::invalidState($payment->status->value),
                RefundDecision::EXCEEDS => throw PaymentRejected::refundExceeds($decision->refundable),
                default => null,
            };
            if ($decision->outcome === RefundDecision::SKIP) {
                return [null, $payment];
            }

            $gateway = $this->gateways->get($payment->gateway_code);
            $automatic = RefundRules::isAutomatic($gateway instanceof RefundsPayments, (bool) $gateway?->capabilities()->partialRefund, $decision->amount, $decision->refundable);
            $actor = $this->context->has() ? $this->context->actor() : null;

            $refund = Refund::query()->create([
                'public_id' => (string) Str::ulid(), 'payment_id' => $payment->id, 'amount' => $decision->amount,
                'status' => RefundStatus::initial($automatic)->value, 'reason' => $reason, 'idempotency_key' => $idempotencyKey,
                'requested_by_type' => $actor?->type->value, 'requested_by_id' => $actor?->id,
            ]);

            $refunded = $payment->refunded_amount + $decision->amount;
            $status = $payment->status->transitionTo(RefundRules::statusAfterRefund((int) $payment->amount, $refunded));
            $payment->update(['refunded_amount' => $refunded, 'status' => $status, 'lock_version' => $payment->lock_version + 1]);
            $this->transitions->setPaymentStatus($payment->order_id, $status->value, "refund:{$refund->public_id}", 'system');
            $this->audit->record('payment.refund_created', 'payment', $payment->id, ['refund' => $refund->public_id, 'amount' => $decision->amount, 'reason' => $reason]);
            event(new RefundCreated($refund->id, $payment->id, $payment->order_id, $decision->amount, $refund->status));

            return [$refund, $payment];
        });

        if ($refund !== null && $refund->status === RefundStatus::Processing->value) {
            // Đang trong transaction của nghiệp vụ gọi (vd. hoàn tất đổi trả): chỉ gọi cổng khi nghiệp vụ đó commit.
            DB::afterCommit(fn () => $this->executeGatewayRefund($refund, $payment));
        }
    }

    private function executeGatewayRefund(Refund $refund, Payment $payment): void
    {
        $gateway = $this->gateways->get($payment->gateway_code);
        ExternalCalls::guard("payment.refund:{$payment->gateway_code}");

        try {
            $result = $gateway instanceof RefundsPayments ? $gateway->refund($this->records->toData($payment), Money::of((int) $refund->amount, $payment->currency_code), $refund->idempotency_key) : null;
        } catch (Throwable $exception) {
            Log::error('Hoàn tiền qua cổng lỗi — chờ nhân viên xử lý.', ['refund' => $refund->public_id, 'exception' => $exception]);
            $result = null;
        }

        DB::transaction(function () use ($refund, $payment, $result): void {
            if ($result?->successful) {
                $refund->update(['status' => RefundStatus::Completed->value, 'gateway_reference' => $result->gatewayReference]);
                $this->records->recordTransaction($payment, 'refund', $result->gatewayReference ?? "refund:{$refund->public_id}", -1 * (int) $refund->amount, 'completed', []);
                event(new RefundCompleted($refund->id, $payment->id, $payment->order_id, (int) $refund->amount, $refund->public_id, $payment->public_id));
            } else {
                // Không hoàn được tự động → chuyển sang chờ nhân viên hoàn thủ công (số đã cam kết hoàn giữ nguyên).
                $refund->update(['status' => RefundStatus::Requested->value]);
            }
        });
    }

    /** Chuyển trạng thái khoản hoàn có điều kiện (giành/nhả quyền chuyển trả); trả số dòng đổi. */
    private function claim(int $refundId, RefundStatus $from, RefundStatus $to): int
    {
        return Refund::query()->whereKey($refundId)->where('status', $from->value)->update(['status' => $to->value, 'updated_at' => now()]);
    }
}
