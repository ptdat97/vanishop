<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Store `database` chỉ xoá dòng hết hạn khi có người đọc đúng khoá đó — token không ai dùng, bản cache trang của URL không
 * ai xem lại nằm lại mãi. Lệnh này xoá theo lô các dòng đã hết hạn của store DurableCache và store mặc định (bỏ qua store
 * không phải `database`, vd. Redis tự hết hạn).
 */
final class PruneDurableCacheCommand extends Command
{
    private const CHUNK = 5000;

    protected $signature = 'vani:cache:prune-durable';

    protected $description = 'Xoá dòng đã hết hạn của store cache dùng bảng database: durable (token, bản xem trước nhập file) và store mặc định (bản cache trang, ADR-051)';

    public function handle(): int
    {
        $stores = array_unique([(string) config('cache.durable_store', 'durable'), (string) config('cache.default')]);
        $deleted = 0;
        $pruned = false;
        foreach ($stores as $name) {
            $store = (array) config('cache.stores.'.$name, []);
            if (($store['driver'] ?? null) !== 'database') {
                continue;
            }
            $pruned = true;
            $deleted += $this->prune(DB::connection($store['connection'] ?? null)->table((string) $store['table']));
        }

        $this->line($pruned ? "Đã xoá {$deleted} dòng hết hạn." : 'Không store nào dùng bảng database — bỏ qua.');

        return self::SUCCESS;
    }

    private function prune(Builder $table): int
    {
        $deleted = 0;
        do {
            $keys = (clone $table)->where('expiration', '<=', now()->getTimestamp())->limit(self::CHUNK)->pluck('key')->all();
            if ($keys !== []) {
                $deleted += (clone $table)->whereIn('key', $keys)->delete();
            }
        } while (count($keys) === self::CHUNK);

        return $deleted;
    }
}
