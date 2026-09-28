<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Gateways;

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
 * Thu tiền khi giao. Ghi nhận đã thu (cod_collected) do Fulfillment/đối soát ở slice Shipment.
 */
final class CodGateway implements PaymentGateway
{
    public function __construct(private readonly ?int $maxAmount) {}

    public function code(): string
    {
        return 'cod';
    }

    public function label(): string
    {
        return __('payment::messages.cod');
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities;
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return $this->maxAmount === null || $context->amount->amount <= $this->maxAmount;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        return PaymentInitiation::none();
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        throw new InvalidCallback('COD không có callback.');
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        return new GatewayStatus(GatewayCallback::PENDING);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        return new GatewayResult(false, null, 'COD hoàn tiền thủ công.');
    }
}
