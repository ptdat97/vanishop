<?php

declare(strict_types=1);

namespace Plugin\BankStatement;

use Illuminate\Http\UploadedFile;
use Modules\Extension\PluginServiceProvider;
use Modules\Shared\Support\SpreadsheetRows;
use Plugin\BankStatement\Infrastructure\StatementMatcher;

/**
 * Đối soát sao kê ngân hàng (EP v3 X5 — tham chiếu cho `Payments`, `Reconciliations` và `adminTool` trên trang Thanh toán).
 */
final class BankStatementServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.bank-statement';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-bank-statement');
        $this->adminTool('payment', 'import_statement', 'vani-bank-statement::texts.bank_statement_service_provider.import_bank_statement', 'payments.confirm', handle: function (UploadedFile $file): string {
            $result = $this->app->make(StatementMatcher::class)->import(SpreadsheetRows::fromUpload($file));

            return __('vani-bank-statement::texts.bank_statement_service_provider.confirmed_confirmed_transfers_mismatched_mismatched', ['confirmed' => $result['confirmed'], 'mismatched' => $result['mismatched'], 'duplicate' => $result['duplicate']]);
        }, help: 'vani-bank-statement::texts.bank_statement_service_provider.xlsx_csv_file_exported_bank');
    }
}
