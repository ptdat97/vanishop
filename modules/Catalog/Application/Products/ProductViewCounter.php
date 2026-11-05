<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Events\ProductViewed;
use Modules\Catalog\Persistence\Models\Style;

/**
 * Ghi lượt xem (§3.5-2): cùng người xem (phiên) + cùng mẫu trong DEDUPE_MINUTES phút chỉ tính một; cộng vào dòng của giờ
 * hiện tại (upsert). Mẫu không còn hiển thị → bỏ qua.
 */
final class ProductViewCounter implements ProductViewStatistics
{
    private const DEDUPE_MINUTES = 30;

    public function __construct(private readonly Cache $cache) {}

    public function record(int $styleId, string $viewer, ?int $customerId): bool
    {
        if (! $this->cache->add('vani:view:'.hash('sha256', "{$viewer}|{$styleId}"), 1, now()->addMinutes(self::DEDUPE_MINUTES))) {
            return false;
        }
        if (! Style::query()->whereKey($styleId)->where('status', 'active')->exists()) {
            return false;
        }

        $hour = now()->utc()->startOfHour()->toDateTimeString();
        DB::table('style_view_stats')->upsert([['style_id' => $styleId, 'hour' => $hour, 'views' => 1]], ['style_id', 'hour'], ['views' => DB::raw('style_view_stats.views + 1')]);
        event(new ProductViewed($styleId, $customerId));

        return true;
    }

    public function counts(array $styleIds, int $days = 30): array
    {
        if ($styleIds === []) {
            return [];
        }

        return DB::table('style_view_stats')->whereIn('style_id', $styleIds)->where('hour', '>=', $this->since($days))
            ->groupBy('style_id')->selectRaw('style_id, sum(views) as total')->pluck('total', 'style_id')
            ->map(fn ($total): int => (int) $total)->all();
    }

    public function top(int $days = 30, int $limit = 10): array
    {
        return DB::table('style_view_stats')->where('hour', '>=', $this->since($days))
            ->groupBy('style_id')->selectRaw('style_id, sum(views) as total')->orderByDesc('total')->limit(max(1, $limit))
            ->pluck('total', 'style_id')->map(fn ($total): int => (int) $total)->all();
    }

    private function since(int $days): string
    {
        return now()->utc()->subDays(max(1, $days))->startOfHour()->toDateTimeString();
    }
}
