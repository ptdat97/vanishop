<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Feature\Fixtures;

use Illuminate\Http\Request;
use Modules\Payment\Application\Gateways\CodGateway;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\GatewayResult;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Shared\Domain\Money\Money;

/**
 * Cổng "trả tại cửa" giả lập — mã khác "cod" nhưng thu tiền khi giao.
 */
final class FakeCollectOnDeliveryGateway implements PaymentGateway
{
    public function __construct(private readonly CodGateway $cod = new CodGateway(null)) {}

    public function code(): string
    {
        return 'fake_pay_at_door';
    }

    public function label(): string
    {
        return 'Trả tại cửa';
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(collectsOnDelivery: true);
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return true;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        return $this->cod->initiate($payment);
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        return $this->cod->verifyCallback($request);
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        return $this->cod->query($payment);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        return $this->cod->refund($payment, $amount, $idempotencyKey);
    }
}
