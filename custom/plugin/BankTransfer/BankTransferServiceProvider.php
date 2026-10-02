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
            ['key' => 'bank', 'label' => 'Ngân hàng'],
            ['key' => 'account_number', 'label' => 'Số tài khoản', 'help' => 'Thiếu số tài khoản → phương thức ẩn ở checkout.'],
            ['key' => 'account_name', 'label' => 'Chủ tài khoản'],
        ]);

        $this->contribute(PaymentGateway::TAG, ManualBankTransferGateway::class);
    }
}
