<?php

declare(strict_types=1);

namespace Modules\Payment\Console;

use Illuminate\Console\Command;
use Modules\Payment\Application\RefundService;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Chốt khoản hoàn còn "đang hoàn" qua cổng: cổng hoàn bất đồng bộ (`Pending`) hoặc tiến trình chết trước khi gọi cổng.
 */
final class SyncRefundsCommand extends Command
{
    protected $signature = 'vani:payment:refunds {--older-than=10 : Chỉ khoản hoàn đang hoàn quá số phút này}';

    protected $description = 'Hỏi cổng trạng thái các khoản hoàn đang xử lý';

    public function handle(CurrentContext $context, RefundService $refunds): int
    {
        $settled = $context->runAs(ContextScope::system('vani:payment:refunds'), fn (): int => $refunds->syncProcessing(max(1, (int) $this->option('older-than'))));

        $this->info("Đã chốt {$settled} khoản hoàn.");

        return self::SUCCESS;
    }
}
