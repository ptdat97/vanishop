<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\Data\PlacedOrder;
use Modules\Payment\Contracts\Data\CallbackOutcome;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentView;
use Modules\Payment\Contracts\PaymentRejected;
use Modules\Payment\Contracts\Payments;
use Modules\Payment\Contracts\RefundPayout;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\ExternalCalls;
use Throwable;

/**
 * Service contract `Payments` (docs/10-payment/payment.md): chọn cổng, tạo/khởi tạo/thử lại khoản thanh toán, đọc khoản.
 * Ghi nhận tiền: `PaymentSettlement`; hoàn tiền: `RefundService`; luật trạng thái và tiền: `Domain/` (architecture-
 * consolidation D1). Các method dưới đây giữ nguyên API cho listener, controller, lệnh và plugin.
 */
final class PaymentService implements Payments
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly AuditLogger $audit,
        private readonly Extensions $extensions,
        private readonly PaymentRecords $records,
        private readonly PaymentSettlement $settlement,
        private readonly RefundService $refunds,
    ) {}

    public function availableMethods(Money $amount): array
    {
        $context = new PaymentContext($amount);
        $methods = [];
        foreach ($this->gateways->all() as $gateway) {
            // Cổng đang ngừng (plugin draining) → không nhận thanh toán mới; cổng lỗi khi kiểm tra → ẩn (không hỏng checkout).
            if ($this->extensions->acceptsNewTransactions($gateway) && $this->extensions->call($gateway, fn (): bool => $gateway->isAvailable($context), false, 'payment.is_available')) {
                $methods[] = ['code' => $gateway->code(), 'label' => $gateway->label()];
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

    public function collectOnDeliveryGateway(): ?string
    {
        foreach ($this->gateways->all() as $code => $gateway) {
            if ($gateway->capabilities()->collectsOnDelivery && $this->extensions->acceptsNewTransactions($gateway)) {
                return (string) $code;
            }
        }

        return null;
    }

    public function offlineGateways(): array
    {
        $codes = [];
        foreach ($this->gateways->all() as $code => $gateway) {
            if ($gateway->capabilities()->collectsOnDelivery || $gateway->capabilities()->manualConfirmation) {
                $codes[] = (string) $code;
            }
        }

        return $codes;
    }

    public function createForOrder(PlacedOrder $order, string $gatewayCode): array
    {
        $gateway = $this->gateways->get($gatewayCode) ?? throw PaymentRejected::gatewayUnavailable($gatewayCode);
        $ttl = $gateway->capabilities()->paymentTtlSeconds;

        $payment = Payment::query()->create([
            'public_id' => (string) Str::ulid(), 'order_id' => $order->id,
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
            return $this->records->toView($payment);
        }

        ExternalCalls::guard("payment.initiate:{$payment->gateway_code}");
        try {
            $initiation = $gateway->initiate($this->records->toData($payment));
        } catch (Throwable $exception) {
            Log::error('Khởi tạo thanh toán lỗi — đơn vẫn giữ nguyên, khách có thể thử lại.', ['payment' => $payment->public_id, 'gateway' => $payment->gateway_code, 'exception' => $exception]);

            return $this->records->toView($payment, __('payment::messages.initiate_failed'));
        }

        $payment->update([
            'gateway_reference' => $initiation->gatewayReference ?? $payment->gateway_reference,
            'meta' => [...($payment->meta ?? []), 'action' => $this->records->serializeAction($initiation)],
        ]);
        $this->records->recordTransaction($payment, 'initiate', $initiation->gatewayReference ?? "initiate:{$payment->public_id}", null, 'ok', []);

        return $this->records->toView($payment);
    }

    public function view(string $paymentPublicId): ?PaymentView
    {
        $payment = Payment::query()->where('public_id', $paymentPublicId)->first();

        return $payment === null ? null : $this->records->toView($payment);
    }

    public function latestForOrder(int $orderId): ?PaymentView
    {
        $payment = Payment::query()->where('order_id', $orderId)->latest('id')->first();

        return $payment === null ? null : $this->records->toView($payment);
    }

    public function retry(int $orderId, ?string $gatewayCode = null): PaymentView
    {
        $publicId = DB::transaction(function () use ($orderId, $gatewayCode): string {
            $current = Payment::query()->where('order_id', $orderId)->latest('id')->lockForUpdate()->first() ?? throw PaymentRejected::notFound();
            if (! $current->status->canRetry()) {
                throw PaymentRejected::invalidState($current->status->value);
            }
            if ($current->expires_at !== null && ! $current->expires_at->isFuture()) {
                throw PaymentRejected::expired();
            }
            $target = $gatewayCode ?? $current->gateway_code;
            if (! $this->records->online($target)) {
                throw PaymentRejected::gatewayUnavailable($target);
            }
            if ($current->status === PaymentStatus::Pending && $target === $current->gateway_code) {
                return $current->public_id;
            }

            // Lượt mới: cổng thường không nhận lại mã giao dịch đã lỗi. Giữ hạn cũ — hàng của đơn giữ tới hạn đó.
            $current->update(['status' => $current->status->transitionTo(PaymentStatus::Cancelled), 'lock_version' => $current->lock_version + 1]);
            $this->records->recordTransaction($current, 'retry_cancel', "retry:{$current->public_id}", null, 'ok', []);
            $next = Payment::query()->create([
                'public_id' => (string) Str::ulid(), 'order_id' => $current->order_id, 'gateway_code' => $target,
                'amount' => $current->amount, 'currency_code' => $current->currency_code, 'status' => PaymentStatus::Pending,
                'expires_at' => $current->expires_at, 'meta' => ['retry_of' => $current->public_id],
            ]);
            $this->audit->record('payment.retried', 'payment', $next->id, ['from' => $current->public_id, 'gateway' => $target]);

            return $next->public_id;
        });

        return $this->initiate($publicId);
    }

    /**
     * Callback đã xác minh chữ ký. Trả false nếu là bản trùng (đã xử lý trước đó).
     */
    public function applyCallback(string $gatewayCode, GatewayCallback $callback, string $type = 'callback'): bool
    {
        return $this->settlement->processCallback($gatewayCode, $callback, $type) !== CallbackOutcome::Duplicate;
    }

    public function processCallback(string $gatewayCode, GatewayCallback $callback, string $type = 'callback'): CallbackOutcome
    {
        return $this->settlement->processCallback($gatewayCode, $callback, $type);
    }

    public function captureAuthorized(int $paymentId, string $source): void
    {
        $this->settlement->captureAuthorized($paymentId, $source);
    }

    public function confirmManually(int $paymentId, string $note): void
    {
        $this->settlement->confirmManually($paymentId, $note);
    }

    public function collectCod(int $orderId, int $amount, string $shipmentReference, ?int $expected = null): void
    {
        $this->settlement->collectCod($orderId, $amount, $shipmentReference, $expected);
    }

    public function collectCodManually(int $paymentId, string $note): void
    {
        $this->settlement->collectCodManually($paymentId, $note);
    }

    public function canCollectCodManually(Payment $payment): bool
    {
        return $this->settlement->canCollectCodManually($payment);
    }

    public function expire(int $paymentId): bool
    {
        return $this->settlement->expire($paymentId);
    }

    public function settleCancelledOrder(int $orderId, string $reason): void
    {
        $this->settlement->settleCancelledOrder($orderId, $reason);
    }

    public function refund(int $paymentId, Money $amount, string $reason, string $idempotencyKey): void
    {
        $this->refunds->refund($paymentId, $amount, $reason, $idempotencyKey);
    }

    public function refundRemaining(int $paymentId, string $reason, string $idempotencyKey): void
    {
        $this->refunds->refundRemaining($paymentId, $reason, $idempotencyKey);
    }

    public function refundOrder(int $orderId, Money $amount, string $reason, string $idempotencyKey): void
    {
        $this->refunds->refundOrder($orderId, $amount, $reason, $idempotencyKey);
    }

    public function settlePartialCancellation(int $orderId, int $amount, string $cancellationId, string $reason): void
    {
        $this->refunds->settlePartialCancellation($orderId, $amount, $cancellationId, $reason);
    }

    /**
     * @return array<string, RefundPayout>
     */
    public function payouts(): array
    {
        return $this->refunds->payouts();
    }

    public function payoutRefund(int $refundId, string $channel): string
    {
        return $this->refunds->payoutRefund($refundId, $channel);
    }

    public function completeManualRefund(int $refundId, string $note): void
    {
        $this->refunds->completeManualRefund($refundId, $note);
    }

    public function settlementsForOrder(int $orderId): array
    {
        $payments = Payment::query()->where('order_id', $orderId)->orderBy('id')->get();
        $byId = $payments->keyBy('id');

        return [
            // Đã từng thu (kể cả đã hoàn hết) — khác hasCollected() (còn hoàn tiếp được).
            'captures' => $payments->filter(fn (Payment $payment): bool => $payment->status->everCollected())->map(fn (Payment $payment): array => [
                'payment_id' => $payment->public_id, 'gateway' => $payment->gateway_code, 'amount' => (int) $payment->amount, 'currency' => $payment->currency_code,
            ])->values()->all(),
            'refunds' => Refund::query()->whereIn('payment_id', $payments->pluck('id'))->where('status', 'completed')->orderBy('id')->get()
                ->map(fn (Refund $refund): array => [
                    'refund_id' => $refund->public_id, 'payment_id' => (string) $byId[$refund->payment_id]->public_id, 'amount' => $refund->amount,
                    'currency' => (string) $byId[$refund->payment_id]->currency_code,
                ])->values()->all(),
        ];
    }

    public function paymentId(string $paymentPublicId): ?int
    {
        $id = Payment::query()->where('public_id', $paymentPublicId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function toData(Payment $payment): PaymentData
    {
        return $this->records->toData($payment);
    }
}
