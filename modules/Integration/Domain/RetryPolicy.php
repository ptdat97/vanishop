<?php

declare(strict_types=1);

namespace Modules\Integration\Domain;

use InvalidArgumentException;

/**
 * Backoff 1m, 5m, 15m, 1h, 6h, 24h (± jitter). Hết lượt → dead.
 *
 * @see docs/11-integration/integration-platform.md §7
 */
final readonly class RetryPolicy
{
    /**
     * @param  list<int>  $delays  giây chờ trước lần thử lại thứ 1, 2, …
     * @param  float  $jitter  tỉ lệ dao động ngẫu nhiên (0.2 = ±20%)
     */
    public function __construct(
        public array $delays = [60, 300, 900, 3600, 21600, 86400],
        public float $jitter = 0.2,
    ) {
        if ($delays === [] || $jitter < 0 || $jitter >= 1) {
            throw new InvalidArgumentException('RetryPolicy không hợp lệ.');
        }
    }

    /**
     * Số giây chờ trước lần thử kế tiếp sau $failedAttempts lần thất bại; null = hết lượt (dead).
     *
     * @param  callable(int, int): int  $random  (min, max) → số ngẫu nhiên; mặc định random_int
     */
    public function delayAfter(int $failedAttempts, ?callable $random = null): ?int
    {
        if ($failedAttempts < 1) {
            throw new InvalidArgumentException('Số lần thất bại phải ≥ 1.');
        }

        $base = $this->delays[$failedAttempts - 1] ?? null;
        if ($base === null) {
            return null;
        }

        $spread = (int) floor($base * $this->jitter);
        $offset = $spread === 0 ? 0 : ($random ?? random_int(...))(-$spread, $spread);

        return max(1, $base + $offset);
    }

    public function maxRetries(): int
    {
        return count($this->delays);
    }
}
