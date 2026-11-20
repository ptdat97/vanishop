<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Payment\Contracts\Data\InitiationType;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\Data\PaymentView;
use Modules\Payment\Contracts\Reconciliations;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\StoreClock;

/**
 * Phần dùng chung của Payment: sổ giao dịch (`payment_transactions`, unique chống ghi trùng), "Đối soát lệch", khả năng
 * của cổng, DTO gửi cổng/plugin và view cho API/Admin.
 */
final class PaymentRecords
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly OrderReader $orders,
        private readonly Reconciliations $reconciliations,
    ) {}

    /**
     * Ghi một giao dịch; bản trùng (cùng cổng + mã giao dịch + loại) → false.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordTransaction(Payment $payment, string $type, string $gatewayTransactionId, ?int $amount, string $status, array $payload): bool
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
     * Cổng/hãng báo số tiền khác khoản thanh toán: không ghi nhận, đưa vào hàng đợi "Đối soát lệch" (trước chỉ ghi log —
     * COD hãng thu thiếu thì khoản thanh toán treo "chờ thu" mà không ai thấy). Idempotent theo (khoản, số tiền báo về).
     */
    public function amountMismatch(Payment $payment, Money $amount, string $note, ?int $expected = null): void
    {
        $this->reconciliations->record('payment', "amount:{$payment->public_id}:{$amount->amount}{$amount->currency->code}", false,
            $payment->order_id, $payment->id, $expected ?? (int) $payment->amount, $amount->amount, $note);
    }

    public function collectsOnDelivery(string $gatewayCode): bool
    {
        return $this->gateways->get($gatewayCode)?->capabilities()->collectsOnDelivery ?? false;
    }

    /** Cổng đang bật và thanh toán trực tuyến (không thu khi giao, không xác nhận tay). */
    public function online(string $gatewayCode): bool
    {
        $gateway = $this->gateways->get($gatewayCode);

        return $gateway !== null && ! $gateway->capabilities()->collectsOnDelivery && ! $gateway->capabilities()->manualConfirmation;
    }

    public function toData(Payment $payment): PaymentData
    {
        $order = $this->orders->find($payment->order_id);

        return new PaymentData(
            publicId: $payment->public_id, gatewayCode: $payment->gateway_code, orderNumber: $order->number ?? '',
            amount: Money::of($payment->amount, $payment->currency_code), status: $payment->status->value,
            gatewayReference: $payment->gateway_reference, expiresAt: $payment->expires_at === null ? null : new DateTimeImmutable($payment->expires_at->toIso8601String()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeAction(PaymentInitiation $initiation): array
    {
        return ['type' => $initiation->type->value, 'url' => $initiation->url, 'qr' => $initiation->qrPayload, 'instructions' => $initiation->instructions];
    }

    public function toView(Payment $payment, ?string $actionError = null): PaymentView
    {
        $order = $this->orders->find($payment->order_id);
        $action = $payment->meta['action'] ?? null;

        return vani_filter('vani.service.payment.view', new PaymentView(
            publicId: $payment->public_id, gatewayCode: $payment->gateway_code, status: $payment->status->value, amount: $payment->amount,
            currencyCode: $payment->currency_code, orderPublicId: $order->publicId ?? '', orderNumber: $order->number ?? '',
            expiresAt: StoreClock::iso($payment->expires_at),
            action: $payment->status === PaymentStatus::Pending && is_array($action)
                ? new PaymentInitiation(InitiationType::tryFrom((string) $action['type']) ?? InitiationType::None, $action['url'] ?? null, $action['qr'] ?? null, (array) ($action['instructions'] ?? []))
                : null,
            actionError: $actionError,
            retryable: $payment->status->canRetry()
                && ($payment->expires_at === null || $payment->expires_at->isFuture()) && $this->online($payment->gateway_code),
        ), $payment);
    }
}
