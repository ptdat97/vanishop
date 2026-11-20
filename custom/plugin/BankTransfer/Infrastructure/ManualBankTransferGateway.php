<?php

declare(strict_types=1);

namespace Plugin\BankTransfer\Infrastructure;

use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Tenancy\Contracts\Settings;
use Plugin\BankTransfer\BankTransferServiceProvider;

/**
 * Chuyển khoản thủ công: hiển thị tài khoản của cửa hàng + nội dung = số đơn; nhân viên xác nhận đã nhận tiền
 * (Core: `manualConfirmation`). Tự xác nhận theo sao kê là plugin `vani.vietqr`.
 * Mã `manual_bank_transfer` giữ nguyên từ khi còn nằm trong Core — đơn cũ không đổi.
 */
final class ManualBankTransferGateway implements PaymentGateway
{
    private const FIELDS = ['bank', 'account_number', 'account_name'];

    public function __construct(private readonly Settings $settings) {}

    public function code(): string
    {
        return 'manual_bank_transfer';
    }

    public function label(): string
    {
        return __('vani-bank-transfer::messages.label');
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(manualConfirmation: true, paymentTtlSeconds: (int) config('vani.bank-transfer.ttl', 86_400));
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return $this->account() !== null;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        $account = $this->account() ?? array_fill_keys(self::FIELDS, '');

        return new PaymentInitiation(PaymentInitiation::INSTRUCTIONS, instructions: [
            ...$account,
            'amount' => (string) $payment->amount->amount,
            'transfer_content' => $payment->orderNumber,
        ]);
    }

    /**
     * Tài khoản nhận tiền: cấu hình trong Admin, thiếu thì theo .env.
     *
     * @return array{bank: string, account_number: string, account_name: string}|null
     */
    private function account(): ?array
    {
        $fallback = (array) config('vani.bank-transfer.account', []);
        $account = [];
        foreach (self::FIELDS as $field) {
            $account[$field] = (string) ($this->settings->get(BankTransferServiceProvider::ID, $field) ?? ($fallback[$field] ?? ''));
        }

        return $account['account_number'] === '' ? null : $account;
    }
}
