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
        $this->adminTool('payment', 'import_statement', 'Nhập sao kê ngân hàng', 'payments.confirm', handle: function (UploadedFile $file): string {
            $result = $this->app->make(StatementMatcher::class)->import(SpreadsheetRows::fromUpload($file));

            return "Đã xác nhận {$result['confirmed']} chuyển khoản; {$result['mismatched']} giao dịch lệch (xem \"Đối soát lệch\"); bỏ qua {$result['duplicate']} giao dịch đã nhập.";
        }, help: 'File .xlsx/.csv ngân hàng xuất: cột "Mã GD", "Số tiền", "Nội dung". Chuyển khoản có số đơn trong nội dung và đúng số tiền được xác nhận ngay.');
    }
}
