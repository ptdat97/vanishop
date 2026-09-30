<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Closure;
use Illuminate\Console\Command;

/**
 * Một lượt (scheduler dự phòng) hoặc vòng lặp liên tục có giới hạn thời gian (`--work`, chạy dưới supervisor).
 */
final class WorkLoop
{
    /**
     * @param  Closure(): int  $batch
     */
    public static function run(Command $command, Closure $batch, string $label): int
    {
        if (! $command->option('work')) {
            $command->info("Đã xử lý {$batch()} {$label}.");

            return Command::SUCCESS;
        }

        $deadline = time() + (int) $command->option('max-time');
        while (time() < $deadline) {
            if ($batch() === 0) {
                sleep(max(1, (int) $command->option('sleep')));
            }
        }

        return Command::SUCCESS;
    }
}
