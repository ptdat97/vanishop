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
 * Cổng "trả tại cửa" giả lập — mã khác "cod" nhưng thu tiền khi giao.
 */
final class FakeCollectOnDeliveryGateway implements PaymentGateway
{
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
        return PaymentInitiation::none();
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        throw new InvalidCallback('Không có callback.');
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        return new GatewayStatus(GatewayCallback::PENDING);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        return new GatewayResult(false, null, 'Hoàn tiền thủ công.');
    }
}
