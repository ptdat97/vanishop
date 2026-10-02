<?php

declare(strict_types=1);

namespace Plugin\Cod\Infrastructure;

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
use Modules\Tenancy\Contracts\Settings;
use Plugin\Cod\CodServiceProvider;

/**
 * Thu tiền khi giao. Core ghi nhận đã thu khi vận đơn giao thành công (`collectsOnDelivery`).
 * Mã `cod` giữ nguyên từ khi còn nằm trong Core — đơn cũ không đổi.
 */
final class CodGateway implements PaymentGateway
{
    public function __construct(private readonly Settings $settings) {}

    public function code(): string
    {
        return 'cod';
    }

    public function label(): string
    {
        return __('vani-cod::messages.label');
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(collectsOnDelivery: true);
    }

    public function isAvailable(PaymentContext $context): bool
    {
        $max = $this->settings->get(CodServiceProvider::ID, 'max_amount', config('vani.cod.max_amount'));

        return $max === null || $max === '' || $context->amount->amount <= (int) $max;
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
