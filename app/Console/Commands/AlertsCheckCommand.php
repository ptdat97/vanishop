<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Observability\AlertManager;
use Illuminate\Console\Command;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class AlertsCheckCommand extends Command
{
    protected $signature = 'vani:alerts:check {--dry-run : Chỉ in cảnh báo sẽ gửi, không gửi và không lưu trạng thái}';

    protected $description = 'Đánh giá điều kiện cảnh báo vận hành và báo qua các kênh (email, plugin) — chạy mỗi phút';

    public function handle(AlertManager $alerts, CurrentContext $context): int
    {
        $sent = $context->runAs(ContextScope::system('vani:alerts:check'), fn (): array => $alerts->run((bool) $this->option('dry-run')));
        foreach ($sent as $alert) {
            $this->line($alert->subject().' — '.$alert->detail);
        }
        if ($sent === []) {
            $this->info('Không có cảnh báo cần gửi.');
        }

        return self::SUCCESS;
    }
}
