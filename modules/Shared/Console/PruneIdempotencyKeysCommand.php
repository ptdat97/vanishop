<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Modules\Shared\Application\IdempotencyStore;

final class PruneIdempotencyKeysCommand extends Command
{
    protected $signature = 'vani:idempotency:prune';

    protected $description = 'Xoá Idempotency-Key đã hết hạn (mặc định 24 giờ)';

    public function handle(IdempotencyStore $store): int
    {
        $this->info('Đã xoá '.$store->pruneExpired().' key hết hạn.');

        return self::SUCCESS;
    }
}
