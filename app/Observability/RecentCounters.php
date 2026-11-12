<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Contracts\Cache\Repository;
use Throwable;

/**
 * Bộ đếm theo phút trên cache (stabilization R4) cho metric mà cảnh báo cần đọc lại trong vài phút gần nhất — Pulse
 * phục vụ dashboard, còn cảnh báo cần nguồn luôn có. Cache là Redis ở production nên bộ đếm dùng chung giữa các server.
 * Lỗi cache không bao giờ làm hỏng nghiệp vụ.
 */
final class RecentCounters
{
    /** Metric được giữ theo phút (tên → giữ bao nhiêu phút). */
    public const TRACKED = ['checkout.attempts' => 15, 'checkout.errors' => 15];

    public function __construct(private readonly Repository $cache) {}

    public function record(string $name, int $by = 1): void
    {
        if (! isset(self::TRACKED[$name]) || $by === 0) {
            return;
        }
        $key = $this->key($name, (int) floor(time() / 60));
        try {
            $this->cache->add($key, 0, self::TRACKED[$name] * 60 + 60);
            $this->cache->increment($key, $by);
        } catch (Throwable) {
            // Hệ giám sát lỗi không được làm hỏng checkout.
        }
    }

    /** Tổng trong `$minutes` phút gần nhất (tính cả phút hiện tại). */
    public function sum(string $name, int $minutes): int
    {
        $now = (int) floor(time() / 60);
        $total = 0;
        for ($minute = $now - $minutes + 1; $minute <= $now; $minute++) {
            try {
                $total += (int) $this->cache->get($this->key($name, $minute), 0);
            } catch (Throwable) {
                return 0;
            }
        }

        return $total;
    }

    private function key(string $name, int $minute): string
    {
        return "vani:recent:{$name}:{$minute}";
    }
}
