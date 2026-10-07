<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginDisableCommand extends Command
{
    protected $signature = 'vani:plugin:disable {plugin}
        {--drain : Ngừng nhận giao dịch mới, vẫn xử lý giao dịch đang dở; tự tắt khi xong}
        {--force : Tắt ngay dù còn việc dở dang (thanh toán chờ, vận đơn đang giao) — cần xác nhận}
        {--yes : Xác nhận --force khi chạy không tương tác}';

    protected $description = 'Tắt plugin';

    public function handle(PluginManager $plugins): int
    {
        $plugin = (string) $this->argument('plugin');
        try {
            if ($this->option('drain')) {
                if ($plugins->drain($plugin)) {
                    $this->info("Không còn việc dở dang — đã tắt {$plugin}.");
                } else {
                    $this->info("{$plugin} đang ngừng (draining): không nhận giao dịch mới; tự tắt khi xử lý xong việc dở dang:");
                    foreach ($plugins->inUse($plugin) as $reason) {
                        $this->line("  - {$reason}");
                    }
                }

                return self::SUCCESS;
            }

            $inUse = $this->option('force') ? $plugins->inUse($plugin) : [];
            if ($inUse !== []) {
                $this->warn("Tắt ngay {$plugin} khi còn việc dở dang — callback/webhook sau đó sẽ không được xử lý:");
                foreach ($inUse as $reason) {
                    $this->line("  - {$reason}");
                }
                $confirmed = $this->option('yes') || ($this->input->isInteractive() && $this->confirm('Xác nhận tắt ngay (sẽ ghi audit)?'));
                if (! $confirmed) {
                    $this->error('Đã huỷ. Dùng --drain để ngừng an toàn, hoặc thêm --yes để xác nhận khi chạy không tương tác.');

                    return self::FAILURE;
                }
            }

            $plugins->disable($plugin, (bool) $this->option('force'));
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã tắt {$plugin}.");

        return self::SUCCESS;
    }
}
