<?php

declare(strict_types=1);

namespace Plugin\BankTransfer;

use Modules\Extension\PluginServiceProvider;
use Modules\Payment\Contracts\PaymentGateway;
use Plugin\BankTransfer\Infrastructure\ManualBankTransferGateway;

/**
 * Plugin hệ thống (ADR-029): chuyển khoản ngân hàng, nhân viên xác nhận đã nhận tiền.
 */
final class BankTransferServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.bank-transfer';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/bank-transfer.php'), 'vani.bank-transfer');
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('resources/lang'), 'vani-bank-transfer');
        $this->settings([
            ['key' => 'bank', 'label' => 'vani-bank-transfer::texts.bank_transfer_service_provider.bank'],
            ['key' => 'account_number', 'label' => 'vani-bank-transfer::texts.bank_transfer_service_provider.account_number', 'help' => 'vani-bank-transfer::texts.bank_transfer_service_provider.missing_account_number_method_hidden'],
            ['key' => 'account_name', 'label' => 'vani-bank-transfer::texts.bank_transfer_service_provider.account_holder'],
        ]);

        $this->contribute(PaymentGateway::TAG, ManualBankTransferGateway::class);
    }
}
