<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginHealth;

final class PluginHealthCommand extends Command
{
    protected $signature = 'vani:plugin:health';

    protected $description = 'Kiểm tra sức khoẻ plugin (cấu hình, kết nối) và lưu kết quả cho Admin (mã thoát 1 nếu có lỗi)';

    public function handle(PluginHealth $health): int
    {
        $results = $health->run();
        $this->table(['Plugin', 'Trạng thái', 'Chi tiết'], array_map(fn (string $plugin, array $result): array => [$plugin, $result['status'], $result['message']], array_keys($results), $results));

        return collect($results)->contains('status', 'error') ? self::FAILURE : self::SUCCESS;
    }
}
