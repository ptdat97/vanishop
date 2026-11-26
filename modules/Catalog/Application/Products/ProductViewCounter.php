<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Events\ProductViewed;
use Modules\Catalog\Persistence\Models\Product;

/**
 * Ghi lượt xem (§3.5-2): cùng người xem (phiên) + cùng mẫu trong DEDUPE_MINUTES phút chỉ tính một; cộng vào dòng của giờ
 * hiện tại (upsert). Mẫu không còn hiển thị → bỏ qua.
 */
final class ProductViewCounter implements ProductViewStatistics
{
    private const DEDUPE_MINUTES = 30;

    public function __construct(private readonly Cache $cache) {}

    public function record(int $productId, string $viewer, ?int $customerId): bool
    {
        if (! $this->cache->add('vani:view:'.hash('sha256', "{$viewer}|{$productId}"), 1, now()->addMinutes(self::DEDUPE_MINUTES))) {
            return false;
        }
        if (! Product::query()->whereKey($productId)->where('status', 'active')->exists()) {
            return false;
        }

        $hour = now()->utc()->startOfHour()->toDateTimeString();
        DB::table('product_view_stats')->upsert([['product_id' => $productId, 'hour' => $hour, 'views' => 1]], ['product_id', 'hour'], ['views' => DB::raw('product_view_stats.views + 1')]);
        event(new ProductViewed($productId, $customerId));

        return true;
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
