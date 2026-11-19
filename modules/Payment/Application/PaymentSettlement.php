<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitionRejected;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Payment\Contracts\CapturesLater;
use Modules\Payment\Contracts\Data\CallbackOutcome;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\PaymentRejected;
use Modules\Payment\Domain\CodRules;
use Modules\Payment\Domain\PaymentAmount;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Events\PaymentAuthorized;
use Modules\Payment\Events\PaymentCaptured;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\ExternalCalls;

/**
 * Ghi nhận tiền vào/ra của khoản thanh toán (docs/10-payment/payment.md): callback/IPN, thu khoản giữ tiền, xác nhận tay,
 * thu COD, hết hạn, đơn huỷ. Luôn: khoá payment → ghi giao dịch (unique chống trùng) → so số tiền → đổi trạng thái theo
 * máy trạng thái của Domain → cập nhật đơn, trong cùng transaction. Gọi cổng ngoài transaction.
 */
final class PaymentSettlement
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly AuditLogger $audit,
        private readonly PaymentRecords $records,
        private readonly RefundService $refunds,
    ) {}

    /**
     * Ghi nhận callback/IPN đã xác minh, trả kết quả chi tiết (cổng CallbackResponder dùng để chọn mã phản hồi).
     *
     * @throws PaymentRejected không có khoản thanh toán
     */
    public function processCallback(string $gatewayCode, GatewayCallback $callback, string $type = 'callback'): CallbackOutcome
    {
        return DB::transaction(function () use ($gatewayCode, $callback, $type): CallbackOutcome {
            $payment = Payment::query()->where('public_id', $callback->paymentPublicId)->where('gateway_code', $gatewayCode)->lockForUpdate()->first()
                ?? throw PaymentRejected::notFound();
            // Đã từng thu (kể cả đã hoàn hết) → IPN "đã trả" khác mã giao dịch là bản trùng, không ghi nhận lại.
            $collectedBefore = $payment->status->everCollected();

            if (! $this->records->recordTransaction($payment, $type, $callback->gatewayTransactionId, $callback->amount->amount, $callback->status, $callback->maskedPayload)) {
                return CallbackOutcome::Duplicate;
            }

            $settles = in_array($callback->status, [GatewayCallback::PAID, GatewayCallback::AUTHORIZED], true);
            if ($settles && ! PaymentAmount::matches((int) $payment->amount, $payment->currency_code, $callback->amount)) {
                Log::warning('Số tiền thanh toán không khớp — không ghi nhận, cần kiểm tra.', ['payment' => $payment->public_id, 'expected' => $payment->amount, 'received' => $callback->amount->amount]);

                return CallbackOutcome::AmountMismatch;
            }
            if ($collectedBefore) {
                return CallbackOutcome::Duplicate;
            }

            match ($callback->status) {
                GatewayCallback::PAID => $this->capture($payment, $callback->amount, "gateway:{$gatewayCode}"),
                GatewayCallback::AUTHORIZED => $this->authorize($payment, $callback->amount, "gateway:{$gatewayCode}"),
                GatewayCallback::FAILED => $this->fail($payment, "gateway:{$gatewayCode}"),
                default => null,
            };

            return CallbackOutcome::Applied;
        });
    }

    /**
     * Thu khoản đang giữ tiền qua cổng CapturesLater (vận đơn rời kho hoặc nhân viên bấm). Gọi cổng ngoài transaction;
     * idempotent theo payment — gọi lại sau khi đã thu thì bỏ qua.
     */
    public function captureAuthorized(int $paymentId, string $source): void
    {
        $payment = Payment::query()->findOrFail($paymentId);
        if ($payment->status->everCollected()) {
            return;
        }
        $gateway = $this->gateways->get($payment->gateway_code);
        if ($payment->status !== PaymentStatus::Authorized || ! $gateway instanceof CapturesLater) {
            throw PaymentRejected::invalidState($payment->status->value);
        }

        $amount = Money::of($payment->amount, $payment->currency_code);
        ExternalCalls::guard("payment.capture:{$payment->gateway_code}");
        $result = $gateway->capture($this->records->toData($payment), $amount, "capture:{$payment->public_id}");
        if (! $result->successful) {
            Log::warning('Cổng từ chối thu khoản đã giữ tiền.', ['payment' => $payment->public_id, 'message' => $result->message]);
            throw PaymentRejected::captureFailed((string) $result->message);
        }

        DB::transaction(function () use ($paymentId, $amount, $result, $source): void {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            if ($this->records->recordTransaction($payment, 'capture', $result->gatewayReference ?? "capture:{$payment->public_id}", $amount->amount, GatewayCallback::PAID, [])) {
                $this->capture($payment, $amount, $source);
            }
        });
    }

    public function confirmManually(int $paymentId, string $note): void
    {
        DB::transaction(function () use ($paymentId, $note): void {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            $gateway = $this->gateways->get($payment->gateway_code);
            if ($payment->status->everCollected()) {
                return;
            }
            if ($gateway === null || ! $gateway->capabilities()->manualConfirmation || ! $payment->status->canConfirmManually()) {
                throw PaymentRejected::invalidState($payment->status->value);
            }

            $this->records->recordTransaction($payment, 'confirm', "manual:{$payment->public_id}", $payment->amount, GatewayCallback::PAID, ['note' => $note]);
            $this->capture($payment, Money::of($payment->amount, $payment->currency_code), 'staff');
            $this->audit->record('payment.confirmed_manually', 'payment', $payment->id, ['amount' => $payment->amount, 'note' => $note]);
        });
    }

    /**
     * Hãng báo giao thành công vận đơn có COD → ghi nhận đã thu (cod_collected). Idempotent theo vận đơn.
     *
     * Đơn tách nhiều kiện thu theo giá trị từng kiện (0.5.36, L37): mỗi kiện một giao dịch `cod_collected`, cộng dồn; đủ tổng
     * khoản thanh toán mới chuyển "đã thu"; thu vượt → "Đối soát lệch" như trước.
     */
    public function collectCod(int $orderId, int $amount, string $shipmentReference, ?int $expected = null): void
    {
        DB::transaction(function () use ($orderId, $amount, $shipmentReference, $expected): void {
            $payment = Payment::query()->where('order_id', $orderId)->lockForUpdate()->get()
                ->first(fn (Payment $candidate): bool => $this->records->collectsOnDelivery($candidate->gateway_code));
            if ($payment === null || $payment->status->everCollected()) {
                return;
            }
            $collected = (int) DB::table('payment_transactions')->where('payment_id', $payment->id)
                ->where('type', 'cod_collected')->where('status', GatewayCallback::PAID)->sum('amount');
            $expected ??= (int) $payment->amount - $collected;
            if ($amount !== $expected) {
                // Hãng thu thiếu/thừa so với kiện (hoặc so với phần còn phải thu): không ghi nhận, vào "Đối soát lệch".
                Log::warning('Số tiền thu hộ không khớp — không ghi nhận, cần kiểm tra.', ['payment' => $payment->public_id, 'expected' => $expected, 'received' => $amount]);
                $this->records->amountMismatch($payment, Money::of($amount, $payment->currency_code), __('payment::texts.payment_settlement.captured_amount_does_not_match'), $expected);

                return;
            }
            if (! $this->records->recordTransaction($payment, 'cod_collected', $shipmentReference, $amount, GatewayCallback::PAID, [])) {
                return;
            }
            if ($collected + $amount < (int) $payment->amount) {
                // Còn kiện chưa giao: chờ đủ tiền mới ghi nhận đã thu.
                return;
            }

            $this->capture($payment, Money::of($collected + $amount, $payment->currency_code), 'carrier', 'cod_collected');
        });
    }

    /**
     * Nhân viên ghi nhận đã thu tiền khi giao (shop tự giao, hãng không báo giao thành công, đơn hoàn tất ngoài trình tự).
     * Idempotent: đã thu thì bỏ qua; hãng báo giao sau đó không ghi nhận lần hai. Đơn đã huỷ thì từ chối.
     */
    public function collectCodManually(int $paymentId, string $note): void
    {
        DB::transaction(function () use ($paymentId, $note): void {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            if ($payment->status->everCollected()) {
                return;
            }
            if (! $this->canCollectCodManually($payment) || $this->orders->find($payment->order_id)?->status === OrderStatus::Cancelled) {
                throw PaymentRejected::invalidState($payment->status->value);
            }

            $this->records->recordTransaction($payment, 'cod_collected', "manual:{$payment->public_id}", $payment->amount, GatewayCallback::PAID, ['note' => $note]);
            $this->capture($payment, Money::of($payment->amount, $payment->currency_code), 'staff', 'cod_collected');
            $this->audit->record('payment.cod_collected_manually', 'payment', $payment->id, ['amount' => $payment->amount, 'note' => $note]);
        });
    }

    /**
     * Khoản thu khi giao đang chờ — hiện nút "Đã thu tiền" ở Admin (đơn huỷ thì khoản này đã bị huỷ theo đơn).
     */
    public function canCollectCodManually(Payment $payment): bool
    {
        return CodRules::canCollectManually($payment->status, (int) $payment->amount, $this->records->collectsOnDelivery($payment->gateway_code));
    }

    /**
     * Hết hạn thanh toán → payment expired + huỷ đơn (OrderCancelled nhả hàng, hoàn lượt khuyến mãi).
     */
    public function expire(int $paymentId): bool
    {
        return DB::transaction(function () use ($paymentId): bool {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            if (! $payment->status->closesWithoutCollection() || $payment->expires_at === null || $payment->expires_at->isFuture()) {
                return false;
            }

            $this->move($payment, PaymentStatus::Expired);
            $order = $this->orders->find($payment->order_id);
            if ($order !== null && $order->status === OrderStatus::Pending) {
                $this->transitions->transition($order->id, OrderStatus::Cancelled, 'payment_timeout', 'system');
            }

            return true;
        });
    }

    /**
     * Đơn huỷ → huỷ payment chưa thu; payment đã thu thì hoàn tiền (tự động nếu cổng hỗ trợ, không thì chờ nhân viên).
     */
    public function settleCancelledOrder(int $orderId, string $reason): void
    {
        foreach (Payment::query()->where('order_id', $orderId)->pluck('id') as $paymentId) {
            // Quyết định theo trạng thái đọc trong khoá: IPN có thể vừa ghi nhận tiền giữa lúc đơn huỷ và lúc này.
            $payment = DB::transaction(function () use ($paymentId): Payment {
                $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
                if ($payment->status->closesWithoutCollection()) {
                    $this->move($payment, PaymentStatus::Cancelled);
                }

                return $payment;
            });
            if ($payment->status === PaymentStatus::Authorized) {
                $this->voidAuthorization($payment, $reason);
            } elseif ($payment->status->hasCollected()) {
                $this->refunds->refundRemaining($payment->id, "order_cancelled:{$reason}", "order-cancel:{$orderId}:{$payment->id}");
            }
        }

        // Không khoản nào đã thu/đang giữ → đơn không còn gì để thu: bỏ "COD chờ thu"/"Chưa thanh toán" trên đơn huỷ.
        $open = Payment::query()->where('order_id', $orderId)->get()->contains(fn (Payment $payment): bool => $payment->status->everCollected() || $payment->status === PaymentStatus::Authorized);
        if (! $open && Payment::query()->where('order_id', $orderId)->exists()) {
            $this->transitions->setPaymentStatus($orderId, 'voided', 'order_cancelled', 'system');
        }
    }

    /**
     * Đơn huỷ khi đang giữ tiền: yêu cầu cổng huỷ giữ tiền. Cổng từ chối → giữ trạng thái authorized + log để CSKH xử lý.
     */
    private function voidAuthorization(Payment $payment, string $reason): void
    {
        $gateway = $this->gateways->get($payment->gateway_code);
        ExternalCalls::guard("payment.void:{$payment->gateway_code}");
        $result = $gateway instanceof CapturesLater ? $gateway->void($this->records->toData($payment), "void:{$payment->public_id}") : null;
        if ($result === null || ! $result->successful) {
            Log::warning('Không huỷ được khoản giữ tiền của đơn đã huỷ — cần xử lý thủ công.', ['payment' => $payment->public_id, 'message' => $result?->message]);

            return;
        }

        DB::transaction(function () use ($payment, $result, $reason): void {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === PaymentStatus::Authorized && $this->records->recordTransaction($locked, 'void', $result->gatewayReference ?? "void:{$locked->public_id}", $locked->amount, 'voided', ['reason' => $reason])) {
                $this->move($locked, PaymentStatus::Cancelled);
            }
        });
    }

    private function capture(Payment $payment, Money $amount, string $source, string $orderPaymentStatus = 'paid'): void
    {
        if (! PaymentAmount::matches((int) $payment->amount, $payment->currency_code, $amount)) {
            Log::warning('Số tiền thanh toán không khớp — không ghi nhận, cần kiểm tra.', ['payment' => $payment->public_id, 'expected' => $payment->amount, 'received' => $amount->amount]);
            $this->records->amountMismatch($payment, $amount, __('payment::texts.payment_settlement.captured_amount_does_not_match'));

            return;
        }
        if ($payment->status->everCollected()) {
            return;
        }

        $this->move($payment, PaymentStatus::Paid, ['paid_at' => now()]);
        event(new PaymentCaptured($payment->id, $payment->order_id, $payment->amount, $payment->gateway_code, $payment->public_id));

        $order = $this->orders->find($payment->order_id);
        if ($order === null) {
            return;
        }

        $this->transitions->setPaymentStatus($order->id, $orderPaymentStatus, "payment:{$payment->public_id}", $source);
        $cancelled = $order->status === OrderStatus::Cancelled;
        if ($order->status === OrderStatus::Pending) {
            try {
                $this->transitions->transition($order->id, OrderStatus::Confirmed, 'payment_captured', $source);
            } catch (OrderTransitionRejected $exception) {
                // Đơn vừa bị huỷ giữa lúc đọc và lúc khoá đơn (khách huỷ đúng lúc IPN đến): xử lý như thanh toán muộn.
                $cancelled = $this->orders->find($payment->order_id)?->status === OrderStatus::Cancelled ? true : throw $exception;
            }
        }
        if ($cancelled) {
            // IPN đến sau khi đơn đã huỷ: đã thu tiền → hoàn phần còn lại (huỷ đơn có thể đã hoàn trước), CSKH theo dõi.
            Log::warning('Thanh toán về sau khi đơn đã huỷ — tạo yêu cầu hoàn tiền.', ['payment' => $payment->public_id, 'order' => $order->number]);
            DB::afterCommit(fn () => $this->refunds->refundRemaining($payment->id, 'late_payment_after_cancel', "late-payment:{$payment->id}"));
        }
    }

    /**
     * Cổng báo đã giữ tiền: payment authorized, đơn xác nhận (hàng đã giữ), tiền thu sau.
     */
    private function authorize(Payment $payment, Money $amount, string $source): void
    {
        if (! PaymentAmount::matches((int) $payment->amount, $payment->currency_code, $amount)) {
            Log::warning('Số tiền giữ không khớp — không ghi nhận, cần kiểm tra.', ['payment' => $payment->public_id, 'expected' => $payment->amount, 'received' => $amount->amount]);
            $this->records->amountMismatch($payment, $amount, __('payment::texts.payment_settlement.authorised_amount_does_not_match'));

            return;
        }
        if ($payment->status !== PaymentStatus::Pending) {
            return;
        }

        $this->move($payment, PaymentStatus::Authorized);
        event(new PaymentAuthorized($payment->id, $payment->order_id, $payment->amount, $payment->gateway_code));

        $order = $this->orders->find($payment->order_id);
        if ($order !== null) {
            $this->transitions->setPaymentStatus($order->id, 'authorized', "payment:{$payment->public_id}", $source);
            if ($order->status === OrderStatus::Pending) {
                $this->transitions->transition($order->id, OrderStatus::Confirmed, 'payment_authorized', $source);
            }
        }
    }

    private function fail(Payment $payment, string $source): void
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return;
        }

        $this->move($payment, PaymentStatus::Failed);
        $this->transitions->setPaymentStatus($payment->order_id, 'failed', "payment:{$payment->public_id}", $source);
        event(new PaymentFailed($payment->id, $payment->order_id, $payment->gateway_code));
    }

    /**
     * Đổi trạng thái theo máy trạng thái của Domain (+ khoá lạc quan).
     *
     * @param  array<string, mixed>  $extra
     */
    private function move(Payment $payment, PaymentStatus $to, array $extra = []): void
    {
        $payment->update(['status' => $payment->status->transitionTo($to), 'lock_version' => $payment->lock_version + 1, ...$extra]);
    }
}
