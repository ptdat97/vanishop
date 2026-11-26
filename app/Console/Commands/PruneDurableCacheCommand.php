<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Store `database` chỉ xoá dòng hết hạn khi có người đọc đúng khoá đó — token không ai dùng nằm lại mãi. Lệnh này xoá theo
 * lô các dòng đã hết hạn của store DurableCache (bỏ qua nếu store không phải `database`, vd. Redis tự hết hạn).
 */
final class PruneDurableCacheCommand extends Command
{
    private const CHUNK = 5000;

    protected $signature = 'vani:cache:prune-durable';

    protected $description = 'Xoá dòng đã hết hạn của store cache durable (token, bản xem trước nhập file)';

    public function handle(): int
    {
        $store = (array) config('cache.stores.'.config('cache.durable_store', 'durable'), []);
        if (($store['driver'] ?? null) !== 'database') {
            $this->line('Store durable không dùng bảng database — bỏ qua.');

            return self::SUCCESS;
        }

        $table = DB::connection($store['connection'] ?? null)->table((string) $store['table']);
        $deleted = 0;
        do {
            $keys = (clone $table)->where('expiration', '<=', now()->getTimestamp())->limit(self::CHUNK)->pluck('key')->all();
            if ($keys !== []) {
                $deleted += (clone $table)->whereIn('key', $keys)->delete();
            }
        } while (count($keys) === self::CHUNK);

        $this->line("Đã xoá {$deleted} dòng hết hạn.");

        return self::SUCCESS;
    }
}
