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
 * Chuyển khoản thủ công: hiển thị tài khoản của cửa hàng + nội dung = số đơn; nhân viên xác nhận đã nhận tiền.
 * Tự xác nhận theo sao kê là plugin `vani.vietqr`.
 */
final class ManualBankTransferGateway implements PaymentGateway
{
    /**
     * @param  array{bank?: string, account_number?: string, account_name?: string}  $account
     */
    public function __construct(
        private readonly array $account,
        private readonly int $ttlSeconds,
    ) {}

    public function code(): string
    {
        return 'manual_bank_transfer';
    }

    public function label(): string
    {
        return __('payment::messages.manual_bank_transfer');
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(manualConfirmation: true, paymentTtlSeconds: $this->ttlSeconds);
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return $this->account() !== null;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        $account = $this->account() ?? [];

        return new PaymentInitiation(PaymentInitiation::INSTRUCTIONS, instructions: [
            'bank' => (string) ($account['bank'] ?? ''),
            'account_number' => (string) ($account['account_number'] ?? ''),
            'account_name' => (string) ($account['account_name'] ?? ''),
            'amount' => (string) $payment->amount->amount,
            'transfer_content' => $payment->orderNumber,
        ]);
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        throw new InvalidCallback('Chuyển khoản thủ công không có callback.');
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        return new GatewayStatus(GatewayCallback::PENDING);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        return new GatewayResult(false, null, 'Hoàn tiền chuyển khoản thủ công.');
    }

    /**
     * @return array{bank: string, account_number: string, account_name: string}|null
     */
    private function account(): ?array
    {
        return ($this->account['account_number'] ?? '') === '' ? null : $this->account;
    }
}
