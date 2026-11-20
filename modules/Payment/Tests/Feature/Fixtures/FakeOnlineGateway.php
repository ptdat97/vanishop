<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature\Fixtures;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\GatewayPaymentStatus;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\Data\RefundOutcome;
use Modules\Payment\Contracts\Data\RefundResult;
use Modules\Payment\Contracts\Data\ReturnResult;
use Modules\Payment\Contracts\HandlesReturn;
use Modules\Payment\Contracts\InvalidCallback;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\QueriesStatus;
use Modules\Payment\Contracts\ReceivesCallbacks;
use Modules\Payment\Contracts\TracksRefunds;
use Modules\Shared\Domain\Money\Money;

/**
 * Cổng giả kiểu redirect + IPN ký HMAC — mô phỏng plugin cổng online trong test.
 */
final class FakeOnlineGateway implements HandlesReturn, PaymentGateway, QueriesStatus, ReceivesCallbacks, TracksRefunds
{
    public const SECRET = 'fake-secret';

    /** @var array<string, string> */
    public static array $refunds = [];

    public static ?GatewayStatus $queryResult = null;

    /** Ngữ cảnh lần gọi isAvailable() gần nhất; khoản gửi initiate() gần nhất. */
    public static ?PaymentContext $lastContext = null;

    public static ?PaymentData $lastInitiated = null;

    /** Kết quả lệnh hoàn (mặc định thành công); `refundStatus()` trả `$refundStatus` khi đặt. */
    public static RefundOutcome $refundOutcome = RefundOutcome::Succeeded;

    public static ?RefundResult $refundStatus = null;

    /** Giả lập cổng lỗi khi tra cứu (timeout…). */
    public static ?\Throwable $queryException = null;

    public function code(): string
    {
        return 'fake_online';
    }

    public function label(): string
    {
        return 'Cổng giả';
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(partialRefund: true, paymentTtlSeconds: 900);
    }

    public function isAvailable(PaymentContext $context): bool
    {
        self::$lastContext = $context;

        return true;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        self::$lastInitiated = $payment;

        return new PaymentInitiation(PaymentInitiation::REDIRECT, url: "https://pay.example/checkout/{$payment->publicId}", gatewayReference: "FAKE-{$payment->publicId}");
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        $fields = ['payment_id' => (string) $request->input('payment_id'), 'txn' => (string) $request->input('txn'), 'status' => (string) $request->input('status'), 'amount' => (string) $request->input('amount')];
        if (! hash_equals(self::sign($fields), (string) $request->input('sig')) || in_array('', $fields, true)) {
            throw new InvalidCallback('Chữ ký không hợp lệ.');
        }

        return new GatewayCallback($fields['payment_id'], $fields['txn'], GatewayPaymentStatus::tryFrom($fields['status']) ?? throw new InvalidCallback('Trạng thái lạ.'), Money::vnd((int) $fields['amount']), ['txn' => $fields['txn']], ['RspCode' => '00']);
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        if (self::$queryException !== null) {
            throw self::$queryException;
        }

        return self::$queryResult ?? new GatewayStatus(GatewayPaymentStatus::Pending);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): RefundResult
    {
        self::$refunds[$idempotencyKey] ??= 'RF-'.(count(self::$refunds) + 1);

        return new RefundResult(self::$refundOutcome, self::$refundOutcome === RefundOutcome::Failed ? null : self::$refunds[$idempotencyKey]);
    }

    public function refundStatus(PaymentData $payment, string $idempotencyKey, ?string $gatewayReference): RefundResult
    {
        return self::$refundStatus ?? RefundResult::pending($gatewayReference);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function parseReturn(Request $request): ReturnResult
    {
        $fields = ['payment_id' => (string) $request->query('payment_id'), 'txn' => (string) $request->query('txn'), 'status' => (string) $request->query('status'), 'amount' => (string) $request->query('amount')];
        if (in_array('', $fields, true) || ! hash_equals(self::sign($fields), (string) $request->query('sig'))) {
            return ReturnResult::invalid();
        }

        return new ReturnResult($fields['payment_id'], GatewayPaymentStatus::tryFrom($fields['status']) ?? GatewayPaymentStatus::Pending);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public static function sign(array $fields): string
    {
        return hash_hmac('sha256', implode('|', [$fields['payment_id'], $fields['txn'], $fields['status'], $fields['amount']]), self::SECRET);
    }

    /**
     * @return array<string, string>
     */
    public static function callbackPayload(string $paymentId, string $txn, string $status, int $amount): array
    {
        $fields = ['payment_id' => $paymentId, 'txn' => $txn, 'status' => $status, 'amount' => (string) $amount];

        return [...$fields, 'sig' => self::sign($fields)];
    }
}
