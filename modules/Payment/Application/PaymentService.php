<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\Data\PlacedOrder;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\Data\PaymentView;
use Modules\Payment\Contracts\PaymentRejected;
use Modules\Payment\Contracts\Payments;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Domain\RefundRules;
use Modules\Payment\Events\PaymentCaptured;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Events\RefundCompleted;
use Modules\Payment\Events\RefundCreated;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Throwable;

/**
 * docs/10-payment/payment.md. Ghi nhận tiền luôn: khoá payment → insert transaction (unique chống trùng) →
 * so số tiền → cập nhật payment + đơn trong cùng transaction.
 */
final class PaymentService implements Payments
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly BrandDirectory $brands,
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly AuditLogger $audit,
        private readonly CurrentContext $context,
    ) {}

    public function availableMethods(int $brandId, int $channelId, Money $amount): array
    {
        $brand = $this->brands->find($brandId);
        if ($brand === null) {
            return [];
        }

        $context = new PaymentContext($brandId, $brand->legalEntityId, $channelId, $amount);
        $methods = [];
        foreach ($this->gateways->all() as $gateway) {
            try {
                if ($gateway->isAvailable($context)) {
                    $methods[] = ['code' => $gateway->code(), 'label' => $gateway->label()];
                }
            } catch (Throwable $exception) {
                Log::warning('Cổng thanh toán lỗi khi kiểm tra khả dụng — ẩn cổng.', ['gateway' => $gateway->code(), 'exception' => $exception]);
            }
        }

        return $methods;
    }

    public function paymentTtl(string $gatewayCode): ?int
    {
        return ($this->gateways->get($gatewayCode) ?? throw PaymentRejected::gatewayUnavailable($gatewayCode))->capabilities()->paymentTtlSeconds;
    }

    public function collectsOnDelivery(string $gatewayCode): bool
    {
        return $this->gateways->get($gatewayCode)?->capabilities()->collectsOnDelivery ?? false;
    }

    public function createForOrder(PlacedOrder $order, string $gatewayCode): array
    {
        $gateway = $this->gateways->get($gatewayCode) ?? throw PaymentRejected::gatewayUnavailable($gatewayCode);
        $ttl = $gateway->capabilities()->paymentTtlSeconds;

        $payment = Payment::query()->create([
            'public_id' => (string) Str::ulid(), 'order_id' => $order->id, 'legal_entity_id' => $order->legalEntityId, 'brand_id' => $order->brandId,
            'gateway_code' => $gatewayCode, 'amount' => $order->totalAmount, 'currency_code' => $order->currencyCode,
            'status' => PaymentStatus::Pending, 'expires_at' => $ttl === null ? null : now()->addSeconds($ttl),
        ]);

        return ['public_id' => $payment->public_id, 'ttl' => $ttl];
    }

    public function initiate(string $paymentPublicId): PaymentView
    {
        $payment = Payment::query()->where('public_id', $paymentPublicId)->first() ?? throw PaymentRejected::notFound();
        $gateway = $this->gateways->get($payment->gateway_code);

        if ($payment->status !== PaymentStatus::Pending || $gateway === null) {
            return $this->toView($payment);
        }

        try {
            $initiation = $gateway->initiate($this->toData($payment));
        } catch (Throwable $exception) {
            Log::error('Khởi tạo thanh toán lỗi — đơn vẫn giữ nguyên, khách có thể thử lại.', ['payment' => $payment->public_id, 'gateway' => $payment->gateway_code, 'exception' => $exception]);

            return $this->toView($payment, __('payment::messages.initiate_failed'));
        }

        $payment->update([
            'gateway_reference' => $initiation->gatewayReference ?? $payment->gateway_reference,
            'meta' => [...($payment->meta ?? []), 'action' => $this->serializeAction($initiation)],
        ]);
        $this->recordTransaction($payment, 'initiate', $initiation->gatewayReference ?? "initiate:{$payment->public_id}", null, 'ok', []);

        return $this->toView($payment);
    }

    public function view(string $paymentPublicId): ?PaymentView
    {
        $payment = Payment::query()->where('public_id', $paymentPublicId)->first();

        return $payment === null ? null : $this->toView($payment);
    }

    /**
     * Callback đã xác minh chữ ký. Trả false nếu là bản trùng (đã xử lý trước đó).
     */
    public function applyCallback(string $gatewayCode, GatewayCallback $callback, string $type = 'callback'): bool
    {
        return DB::transaction(function () use ($gatewayCode, $callback, $type): bool {
            $payment = Payment::query()->where('public_id', $callback->paymentPublicId)->where('gateway_code', $gatewayCode)->lockForUpdate()->first()
                ?? throw PaymentRejected::notFound();

            if (! $this->recordTransaction($payment, $type, $callback->gatewayTransactionId, $callback->amount->amount, $callback->status, $callback->maskedPayload)) {
                return false;
            }

            return match ($callback->status) {
                GatewayCallback::PAID => $this->capture($payment, $callback->amount, "gateway:{$gatewayCode}"),
                GatewayCallback::FAILED => $this->fail($payment, "gateway:{$gatewayCode}"),
                default => true,
            };
        });
    }

    public function confirmManually(int $paymentId, string $note): void
    {
        DB::transaction(function () use ($paymentId, $note): void {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            $gateway = $this->gateways->get($payment->gateway_code);
            if ($payment->status->hasCollected()) {
                return;
            }
            if ($gateway === null || ! $gateway->capabilities()->manualConfirmation || ! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Expired], true)) {
                throw PaymentRejected::invalidState($payment->status->value);
            }

            $this->recordTransaction($payment, 'confirm', "manual:{$payment->public_id}", $payment->amount, GatewayCallback::PAID, ['note' => $note]);
            $this->capture($payment, Money::of($payment->amount, $payment->currency_code), 'staff');
            $this->audit->record('payment.confirmed_manually', 'payment', $payment->id, ['amount' => $payment->amount, 'note' => $note]);
        });
    }

    public function refund(int $paymentId, Money $amount, string $reason, string $idempotencyKey): void
    {
        if (Refund::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }

        [$refund, $payment] = DB::transaction(function () use ($paymentId, $amount, $reason, $idempotencyKey): array {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            if (Refund::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return [null, $payment];
            }
            if (! $payment->status->hasCollected()) {
                throw PaymentRejected::invalidState($payment->status->value);
            }

            $refundable = RefundRules::refundable($payment->amount, $payment->refunded_amount);
            if (! $amount->isPositive() || $amount->amount > $refundable) {
                throw PaymentRejected::refundExceeds($refundable);
            }

            $gateway = $this->gateways->get($payment->gateway_code);
            $automatic = $gateway !== null && $gateway->capabilities()->refund
                && ($amount->amount === $refundable || $gateway->capabilities()->partialRefund);
            $actor = $this->context->has() ? $this->context->actor() : null;

            $refund = Refund::query()->create([
                'public_id' => (string) Str::ulid(), 'payment_id' => $payment->id, 'amount' => $amount->amount,
                'status' => $automatic ? 'processing' : 'requested', 'reason' => $reason, 'idempotency_key' => $idempotencyKey,
                'requested_by_type' => $actor?->type->value, 'requested_by_id' => $actor?->id,
            ]);

            $refunded = $payment->refunded_amount + $amount->amount;
            $status = RefundRules::statusAfterRefund($payment->amount, $refunded);
            $payment->update(['refunded_amount' => $refunded, 'status' => $status, 'lock_version' => $payment->lock_version + 1]);
            $this->transitions->setPaymentStatus($payment->order_id, $status->value, "refund:{$refund->public_id}", 'system');
            $this->audit->record('payment.refund_created', 'payment', $payment->id, ['refund' => $refund->public_id, 'amount' => $amount->amount, 'reason' => $reason]);
            event(new RefundCreated($refund->id, $payment->id, $payment->order_id, $amount->amount, $refund->status, $payment->brand_id));

            return [$refund, $payment];
        });

        if ($refund !== null && $refund->status === 'processing') {
            $this->executeGatewayRefund($refund, $payment);
        }
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
     * Nhân viên đã chuyển trả tiền (hoàn thủ công: COD, chuyển khoản).
     */
    public function completeManualRefund(int $refundId, string $note): void
    {
        DB::transaction(function () use ($refundId, $note): void {
            $refund = Refund::query()->whereKey($refundId)->lockForUpdate()->firstOrFail();
            if ($refund->status !== 'requested') {
                return;
            }

            $refund->update(['status' => 'completed']);
            $payment = Payment::query()->whereKey($refund->payment_id)->firstOrFail();
            $this->audit->record('payment.refund_completed', 'refund', $refund->id, ['amount' => $refund->amount, 'note' => $note]);
            event(new RefundCompleted($refund->id, $payment->id, $payment->order_id, (int) $refund->amount, $payment->brand_id));
        });
    }

    /**
     * Hãng báo giao thành công vận đơn có COD → ghi nhận đã thu (cod_collected). Idempotent theo vận đơn.
     * Đối soát số tiền hãng chuyển về là plugin `vani.cod-reconciliation`.
     */
    public function collectCod(int $orderId, int $amount, string $shipmentReference): void
    {
        DB::transaction(function () use ($orderId, $amount, $shipmentReference): void {
            $payment = Payment::query()->where('order_id', $orderId)->lockForUpdate()->get()
                ->first(fn (Payment $candidate): bool => $this->collectsOnDelivery($candidate->gateway_code));
            if ($payment === null || ! $this->recordTransaction($payment, 'cod_collected', $shipmentReference, $amount, GatewayCallback::PAID, [])) {
                return;
            }

            $this->capture($payment, Money::of($amount, $payment->currency_code), 'carrier', 'cod_collected');
        });
    }

    /**
     * Hết hạn thanh toán → payment expired + huỷ đơn (OrderCancelled nhả hàng, hoàn lượt khuyến mãi).
     */
    public function expire(int $paymentId): bool
    {
        return DB::transaction(function () use ($paymentId): bool {
            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed], true) || $payment->expires_at === null || $payment->expires_at->isFuture()) {
                return false;
            }

            $payment->update(['status' => PaymentStatus::Expired, 'lock_version' => $payment->lock_version + 1]);
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
        $payments = Payment::query()->where('order_id', $orderId)->get();
        foreach ($payments as $payment) {
            if ($payment->status === PaymentStatus::Pending || $payment->status === PaymentStatus::Failed) {
                Payment::query()->whereKey($payment->id)->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Failed])->update(['status' => PaymentStatus::Cancelled]);
            } elseif ($payment->status->hasCollected() && RefundRules::refundable($payment->amount, $payment->refunded_amount) > 0) {
                $this->refund($payment->id, Money::of(RefundRules::refundable($payment->amount, $payment->refunded_amount), $payment->currency_code), "order_cancelled:{$reason}", "order-cancel:{$orderId}:{$payment->id}");
            }
        }
    }

    public function toData(Payment $payment): PaymentData
    {
        $order = $this->orders->find($payment->order_id);

        return new PaymentData(
            publicId: $payment->public_id, gatewayCode: $payment->gateway_code, orderNumber: $order->number ?? '', legalEntityId: $payment->legal_entity_id,
            brandId: $payment->brand_id, amount: Money::of($payment->amount, $payment->currency_code), status: $payment->status->value,
            gatewayReference: $payment->gateway_reference, expiresAt: $payment->expires_at === null ? null : new DateTimeImmutable($payment->expires_at->toIso8601String()),
        );
    }

    private function capture(Payment $payment, Money $amount, string $source, string $orderPaymentStatus = 'paid'): bool
    {
        if ($amount->amount !== $payment->amount || $amount->currency->code !== $payment->currency_code) {
            Log::warning('Số tiền thanh toán không khớp — không ghi nhận, cần kiểm tra.', ['payment' => $payment->public_id, 'expected' => $payment->amount, 'received' => $amount->amount]);

            return true;
        }
        if ($payment->status->hasCollected()) {
            return true;
        }

        $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'lock_version' => $payment->lock_version + 1]);
        event(new PaymentCaptured($payment->id, $payment->order_id, $payment->amount, $payment->gateway_code, $payment->brand_id));

        $order = $this->orders->find($payment->order_id);
        if ($order === null) {
            return true;
        }

        $this->transitions->setPaymentStatus($order->id, $orderPaymentStatus, "payment:{$payment->public_id}", $source);
        if ($order->status === OrderStatus::Pending) {
            $this->transitions->transition($order->id, OrderStatus::Confirmed, 'payment_captured', $source);
        } elseif ($order->status === OrderStatus::Cancelled) {
            // IPN đến sau khi đơn đã huỷ (hết hạn): đã thu tiền → hoàn lại, CSKH theo dõi.
            Log::warning('Thanh toán về sau khi đơn đã huỷ — tạo yêu cầu hoàn tiền.', ['payment' => $payment->public_id, 'order' => $order->number]);
            DB::afterCommit(fn () => $this->refund($payment->id, $amount, 'late_payment_after_cancel', "late-payment:{$payment->id}"));
        }

        return true;
    }

    private function fail(Payment $payment, string $source): bool
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return true;
        }

        $payment->update(['status' => PaymentStatus::Failed, 'lock_version' => $payment->lock_version + 1]);
        $this->transitions->setPaymentStatus($payment->order_id, 'failed', "payment:{$payment->public_id}", $source);
        event(new PaymentFailed($payment->id, $payment->order_id, $payment->gateway_code, $payment->brand_id));

        return true;
    }

    private function executeGatewayRefund(Refund $refund, Payment $payment): void
    {
        $gateway = $this->gateways->get($payment->gateway_code);

        try {
            $result = $gateway?->refund($this->toData($payment), Money::of((int) $refund->amount, $payment->currency_code), $refund->idempotency_key);
        } catch (Throwable $exception) {
            Log::error('Hoàn tiền qua cổng lỗi — chờ nhân viên xử lý.', ['refund' => $refund->public_id, 'exception' => $exception]);
            $result = null;
        }

        DB::transaction(function () use ($refund, $payment, $result): void {
            if ($result?->successful) {
                $refund->update(['status' => 'completed', 'gateway_reference' => $result->gatewayReference]);
                $this->recordTransaction($payment, 'refund', $result->gatewayReference ?? "refund:{$refund->public_id}", -1 * (int) $refund->amount, 'completed', []);
                event(new RefundCompleted($refund->id, $payment->id, $payment->order_id, (int) $refund->amount, $payment->brand_id));
            } else {
                // Không hoàn được tự động → chuyển sang chờ nhân viên hoàn thủ công (số đã cam kết hoàn giữ nguyên).
                $refund->update(['status' => 'requested']);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordTransaction(Payment $payment, string $type, string $gatewayTransactionId, ?int $amount, string $status, array $payload): bool
    {
        try {
            DB::table('payment_transactions')->insert([
                'payment_id' => $payment->id, 'gateway_code' => $payment->gateway_code, 'type' => $type, 'gateway_transaction_id' => $gatewayTransactionId,
                'amount' => $amount, 'status' => $status, 'raw_payload_masked' => $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
                'correlation_id' => Context::get('correlation_id'), 'created_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAction(PaymentInitiation $initiation): array
    {
        return ['type' => $initiation->type, 'url' => $initiation->url, 'qr' => $initiation->qrPayload, 'instructions' => $initiation->instructions];
    }

    private function toView(Payment $payment, ?string $actionError = null): PaymentView
    {
        $order = $this->orders->find($payment->order_id);
        $action = $payment->meta['action'] ?? null;

        return new PaymentView(
            publicId: $payment->public_id, gatewayCode: $payment->gateway_code, status: $payment->status->value, amount: $payment->amount,
            currencyCode: $payment->currency_code, orderPublicId: $order->publicId ?? '', orderNumber: $order->number ?? '',
            expiresAt: $payment->expires_at?->toIso8601String(),
            action: $payment->status === PaymentStatus::Pending && is_array($action)
                ? new PaymentInitiation((string) $action['type'], $action['url'] ?? null, $action['qr'] ?? null, (array) ($action['instructions'] ?? []))
                : null,
            actionError: $actionError,
        );
    }
}
