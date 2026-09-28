<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature\Fixtures;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\GatewayResult;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\InvalidCallback;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Shared\Domain\Money\Money;

/**
 * Cổng giả kiểu redirect + IPN ký HMAC — mô phỏng plugin cổng online trong test.
 */
final class FakeOnlineGateway implements PaymentGateway
{
    public const SECRET = 'fake-secret';

    /** @var array<string, string> */
    public static array $refunds = [];

    public static ?GatewayStatus $queryResult = null;

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
        return new GatewayCapabilities(callbacks: true, query: true, refund: true, partialRefund: true, paymentTtlSeconds: 900);
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return true;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        return new PaymentInitiation(PaymentInitiation::REDIRECT, url: "https://pay.example/checkout/{$payment->publicId}", gatewayReference: "FAKE-{$payment->publicId}");
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        $fields = ['payment_id' => (string) $request->input('payment_id'), 'txn' => (string) $request->input('txn'), 'status' => (string) $request->input('status'), 'amount' => (string) $request->input('amount')];
        if (! hash_equals(self::sign($fields), (string) $request->input('sig')) || in_array('', $fields, true)) {
            throw new InvalidCallback('Chữ ký không hợp lệ.');
        }

        return new GatewayCallback($fields['payment_id'], $fields['txn'], $fields['status'], Money::vnd((int) $fields['amount']), ['txn' => $fields['txn']], ['RspCode' => '00']);
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        return self::$queryResult ?? new GatewayStatus(GatewayCallback::PENDING);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        self::$refunds[$idempotencyKey] ??= 'RF-'.(count(self::$refunds) + 1);

        return new GatewayResult(true, self::$refunds[$idempotencyKey]);
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
