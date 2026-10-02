<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature\Fixtures;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\CapturesLater;
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
 * Cổng thẻ giả: IPN báo `authorized` (giữ tiền), Core thu sau qua CapturesLater.
 */
final class FakeCardGateway implements CapturesLater, PaymentGateway
{
    /** @var array<string, string> khoá idempotency → mã giao dịch */
    public static array $captures = [];

    /** @var array<string, string> */
    public static array $voids = [];

    public static bool $failCapture = false;

    public function code(): string
    {
        return 'fake_card';
    }

    public function label(): string
    {
        return 'Thẻ giả';
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(callbacks: true, refund: true, paymentTtlSeconds: 900);
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return true;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        return new PaymentInitiation(PaymentInitiation::REDIRECT, url: "https://card.example/{$payment->publicId}", gatewayReference: "CARD-{$payment->publicId}");
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        $fields = ['payment_id' => (string) $request->input('payment_id'), 'txn' => (string) $request->input('txn'), 'status' => (string) $request->input('status'), 'amount' => (string) $request->input('amount')];
        if (in_array('', $fields, true) || ! hash_equals(FakeOnlineGateway::sign($fields), (string) $request->input('sig'))) {
            throw new InvalidCallback('Chữ ký không hợp lệ.');
        }

        return new GatewayCallback($fields['payment_id'], $fields['txn'], $fields['status'], Money::vnd((int) $fields['amount']));
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        return new GatewayStatus(GatewayCallback::PENDING);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        return new GatewayResult(true, "RF-{$idempotencyKey}");
    }

    public function capture(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        if (self::$failCapture) {
            return new GatewayResult(false, null, 'Hết hạn giữ tiền');
        }
        self::$captures[$idempotencyKey] ??= 'CAP-'.(count(self::$captures) + 1);

        return new GatewayResult(true, self::$captures[$idempotencyKey]);
    }

    public function void(PaymentData $payment, string $idempotencyKey): GatewayResult
    {
        self::$voids[$idempotencyKey] ??= 'VOID-'.(count(self::$voids) + 1);

        return new GatewayResult(true, self::$voids[$idempotencyKey]);
    }
}
