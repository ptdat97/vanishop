<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginUninstallCommand extends Command
{
    protected $signature = 'vani:plugin:uninstall {plugin}
        {--purge : Rollback migration và xoá bảng plg_* của plugin (chặn khi còn tham chiếu/khoá ngoại)}
        {--drop-retained : Cho phép xoá cả bảng data.retained (chứng từ) khi purge — cần xác nhận}
        {--yes : Xác nhận --drop-retained khi chạy không tương tác}';

    protected $description = 'Gỡ plugin (mặc định giữ dữ liệu)';

    public function handle(PluginManager $plugins): int
    {
        $dropRetained = (bool) $this->option('purge') && (bool) $this->option('drop-retained');
        if ($dropRetained && ! $this->option('yes') && ! ($this->input->isInteractive() && $this->confirm('Xoá cả dữ liệu phải lưu giữ (data.retained)? Không khôi phục được.'))) {
            $this->error('Đã huỷ. Xuất/lưu trữ dữ liệu trước; chạy không tương tác thì thêm --yes.');

            return self::FAILURE;
        }

        try {
            $plugins->uninstall((string) $this->argument('plugin'), (bool) $this->option('purge'), $dropRetained);
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã gỡ '.$this->argument('plugin').'.');

        return self::SUCCESS;
    }
}
