<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Events\ProductViewed;
use Modules\Catalog\Persistence\Models\Product;

/**
 * Ghi lượt xem (§3.5-2): cùng người xem (phiên) + cùng mẫu trong DEDUPE_MINUTES phút chỉ tính một. Lượt xem cộng vào bộ đếm
 * trong cache theo (giờ, sản phẩm) — không ghi DB mỗi lượt (dòng nóng tranh khoá, db-refactor G3); `flush()` (lệnh
 * `vani:catalog:flush-views`, mỗi phút) cộng dồn xuống `product_view_stats`. Số liệu trễ tối đa ~1 phút; cache bị xoá/đẩy ra
 * thì mất phần chưa ghi (thống kê gần đúng). `compact()` gộp dữ liệu theo giờ cũ hơn 30 ngày thành theo ngày. Mẫu không còn
 * hiển thị → bỏ qua.
 */
final class ProductViewCounter implements ProductViewStatistics
{
    private const DEDUPE_MINUTES = 30;

    private const PREFIX = 'vani:views:';

    public function __construct(private readonly Cache $cache) {}

    public function record(int $productId, string $viewer, ?int $customerId): bool
    {
        if (! $this->cache->add('vani:view:'.hash('sha256', "{$viewer}|{$productId}"), 1, now()->addMinutes(self::DEDUPE_MINUTES))) {
            return false;
        }
        if (! Product::query()->whereKey($productId)->where('status', 'active')->exists()) {
            return false;
        }

        $hour = now()->utc()->startOfHour()->format('YmdH');
        $key = self::counterKey($hour, $productId);
        // Store `database` không tự tạo khoá khi increment (Redis, array có) — tạo trước bằng add (nguyên tử).
        $this->cache->add($key, 0, now()->addDays(3));
        if ((int) $this->cache->increment($key) === 1) {
            // Lượt đầu của sản phẩm trong giờ: ghi vào chỉ mục để flush biết cần đọc bộ đếm nào.
            $this->cache->lock(self::PREFIX.'lock', 5)->block(3, function () use ($hour, $productId): void {
                $index = (array) $this->cache->get(self::PREFIX.'index', []);
                $index[$hour][$productId] = true;
                $this->cache->put(self::PREFIX.'index', $index, now()->addDays(3));
            });
        }
        event(new ProductViewed($productId, $customerId));

        return true;
    }

    /**
     * Cộng dồn bộ đếm trong cache xuống DB. Trừ đúng số đã đọc (lượt mới đến giữa lúc đọc và ghi vẫn còn cho lần sau); giờ đã
     * qua mà mọi bộ đếm về 0 thì bỏ khỏi chỉ mục.
     *
     * @return int số lượt xem đã ghi
     */
    public function flush(): int
    {
        $written = 0;
        $current = now()->utc()->startOfHour()->format('YmdH');
        foreach (array_keys((array) $this->cache->get(self::PREFIX.'index', [])) as $hour) {
            $index = (array) $this->cache->get(self::PREFIX.'index', []);
            $rows = [];
            foreach (array_keys((array) ($index[$hour] ?? [])) as $productId) {
                $views = (int) $this->cache->get(self::counterKey((string) $hour, (int) $productId), 0);
                if ($views > 0) {
                    $this->cache->decrement(self::counterKey((string) $hour, (int) $productId), $views);
                    $rows[] = ['product_id' => (int) $productId, 'hour' => Carbon::createFromFormat('YmdH', (string) $hour, 'UTC')->startOfHour()->toDateTimeString(), 'views' => $views];
                    $written += $views;
                }
            }
            foreach ($rows as $row) {
                DB::table('product_view_stats')->upsert([$row], ['product_id', 'hour'], ['views' => DB::raw('product_view_stats.views + '.(int) $row['views'])]);
            }
            if ((string) $hour < $current) {
                $this->cache->lock(self::PREFIX.'lock', 5)->block(3, function () use ($hour): void {
                    $index = (array) $this->cache->get(self::PREFIX.'index', []);
                    unset($index[$hour]);
                    $this->cache->put(self::PREFIX.'index', $index, now()->addDays(3));
                });
            }
        }

        return $written;
    }

    /**
     * Gộp dữ liệu theo giờ cũ hơn `$days` ngày thành một dòng mỗi ngày (giờ 00:00 UTC) — giữ tổng, giảm số dòng 24 lần.
     *
     * @return int số dòng theo giờ đã gộp
     */
    public function compact(int $days = 30): int
    {
        $cutoff = now()->utc()->subDays($days)->startOfDay()->toDateTimeString();
        $merged = 0;
        $rows = DB::table('product_view_stats')->where('hour', '<', $cutoff)->orderBy('hour')->get(['product_id', 'hour', 'views']);
        $daily = [];
        foreach ($rows as $row) {
            $day = Carbon::parse((string) $row->hour, 'UTC')->startOfDay()->toDateTimeString();
            if ((string) $row->hour === $day) {
                continue;
            }
            $daily[(int) $row->product_id][$day][] = [(string) $row->hour, (int) $row->views];
        }
        foreach ($daily as $productId => $days) {
            foreach ($days as $day => $hours) {
                DB::transaction(function () use ($productId, $day, $hours, &$merged): void {
                    $sum = array_sum(array_column($hours, 1));
                    DB::table('product_view_stats')->upsert([['product_id' => $productId, 'hour' => $day, 'views' => $sum]], ['product_id', 'hour'], ['views' => DB::raw('product_view_stats.views + '.$sum)]);
                    $merged += DB::table('product_view_stats')->where('product_id', $productId)->whereIn('hour', array_column($hours, 0))->delete();
                });
            }
        }

        return $merged;
    }

    private static function counterKey(string $hour, int $productId): string
    {
        return self::PREFIX."{$hour}:{$productId}";
    }

    public function counts(array $productIds, int $days = 30): array
    {
        if ($productIds === []) {
            return [];
        }

        return DB::table('product_view_stats')->whereIn('product_id', $productIds)->where('hour', '>=', $this->since($days))
            ->groupBy('product_id')->selectRaw('product_id, sum(views) as total')->pluck('total', 'product_id')
            ->map(fn ($total): int => (int) $total)->all();
    }

    public function top(int $days = 30, int $limit = 10): array
    {
        return DB::table('product_view_stats')->where('hour', '>=', $this->since($days))
            ->groupBy('product_id')->selectRaw('product_id, sum(views) as total')->orderByDesc('total')->limit(max(1, $limit))
            ->pluck('total', 'product_id')->map(fn ($total): int => (int) $total)->all();
    }

    private function since(int $days): string
    {
        return now()->utc()->subDays(max(1, $days))->startOfHour()->toDateTimeString();
    }
}
