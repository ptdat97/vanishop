<?php

declare(strict_types=1);

namespace Plugin\ScarcitySignals\Infrastructure;

use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\ScarcitySignals\ScarcitySignalsServiceProvider;

/**
 * Số liệu khan hiếm — chỉ số thật, không có hệ số, sàn hay chế độ bịa số:
 * - **còn lại**: số bán được online (ATS) của từng variant, chỉ lộ ra khi 0 < ATS ≤ ngưỡng;
 * - **đã bán**: số lượng của đơn đã thu tiền hoặc đã rời kho trong N ngày (`OrderStatistics::confirmedQuantitiesByStyle`),
 *   tính một lần mỗi giờ cho mọi sản phẩm (cache), giấu khi dưới mức tối thiểu.
 */
final class Signals
{
    private const CACHE_KEY = 'plg:scarcity:sold:';

    private const CACHE_SECONDS = 3600;

    public function __construct(
        private readonly AvailabilityReader $availability,
        private readonly OrderStatistics $statistics,
    ) {}

    /**
     * Ngưỡng "sắp hết" của sản phẩm: ngưỡng riêng nhỏ nhất trong các danh mục của nó (danh mục chính và danh mục cha —
     * khớp theo id hoặc đường dẫn), không có thì ngưỡng chung. Nhỏ nhất = dè dặt: cảnh báo hiện ít đi, không nhiều lên.
     *
     * @param  list<array{id?: int, slug?: string}>  $categories
     */
    public function threshold(array $categories): int
    {
        $default = self::clamp((int) $this->setting('low_stock_threshold', 5), 1, 100);
        $overrides = self::overrides((string) $this->setting('threshold_overrides', ''));
        $matched = [];
        foreach ($categories as $category) {
            foreach ([(string) ($category['id'] ?? ''), (string) ($category['slug'] ?? '')] as $key) {
                if ($key !== '' && isset($overrides[$key])) {
                    $matched[] = $overrides[$key];
                }
            }
        }

        return $matched === [] ? $default : min($matched);
    }

    /**
     * Mỗi dòng `danh mục = ngưỡng` (danh mục = id hoặc đường dẫn, vd. `dam = 2`). Dòng sai bị bỏ.
     *
     * @return array<string, int>
     */
    public static function overrides(string $text): array
    {
        $result = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (preg_match('/^\s*([a-z0-9][a-z0-9-]*)\s*=\s*(\d{1,3})\s*$/i', $line, $match) === 1 && (int) $match[2] >= 1) {
                $result[strtolower($match[1])] = self::clamp((int) $match[2], 1, 100);
            }
        }

        return $result;
    }

    /**
     * Variant id => số còn lại, chỉ variant có 0 < ATS ≤ ngưỡng.
     *
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    public function lowStock(array $variantIds, int $threshold): array
    {
        if ($variantIds === []) {
            return [];
        }

        return array_filter($this->availability->forVariants($variantIds), fn (int $left): bool => $left > 0 && $left <= $threshold);
    }

    /**
     * Tổng số bán được online của từng nhóm variant (thẻ sản phẩm), một lần đọc tồn cho cả trang.
     *
     * @param  array<int|string, list<int>>  $groups
     * @return array<int|string, int>
     */
    public function totals(array $groups): array
    {
        $ids = array_values(array_unique(array_merge(...array_values($groups ?: [[]]))));
        $ats = $ids === [] ? [] : $this->availability->forVariants($ids);

        return array_map(fn (array $variantIds): int => array_sum(array_intersect_key($ats, array_flip($variantIds))), $groups);
    }

    /**
     * "Đã bán N trong X ngày" của sản phẩm, hoặc null (tắt, chưa đủ mức tối thiểu).
     *
     * @return array{count: int, days: int}|null
     */
    public function sold(int $styleId): ?array
    {
        if (! (bool) $this->setting('show_sold_count', true)) {
            return null;
        }
        $days = $this->windowDays();
        $count = $this->soldCounts($days)[$styleId] ?? 0;

        return $count >= self::clamp((int) $this->setting('sold_min_to_show', 3), 1, 1000) ? ['count' => $count, 'days' => $days] : null;
    }

    /**
     * Tính lại ngay (sau khi đổi cửa sổ thống kê, hoặc lệnh theo lịch).
     */
    public function refresh(): void
    {
        $days = $this->windowDays();
        Cache::put(self::CACHE_KEY.$days, $this->compute($days), self::CACHE_SECONDS);
    }

    public function windowDays(): int
    {
        return self::clamp((int) $this->setting('sold_window_days', 7), 1, 90);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return vani_setting(ScarcitySignalsServiceProvider::ID, $key, $default);
    }

    /**
     * @return array<int, int>
     */
    private function soldCounts(int $days): array
    {
        return Cache::remember(self::CACHE_KEY.$days, self::CACHE_SECONDS, fn (): array => $this->compute($days));
    }

    /**
     * @return array<int, int>
     */
    private function compute(int $days): array
    {
        $to = new DateTimeImmutable;

        return $this->statistics->confirmedQuantitiesByStyle($to->modify("-{$days} days"), $to);
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
