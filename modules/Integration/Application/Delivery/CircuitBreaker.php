<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Delivery;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;

/**
 * Circuit breaker theo connector (integration-platform §6): mở sau N lỗi retryable liên tiếp, thử lại sau
 * `cooldown` giây. Trạng thái nằm trong cache dùng chung giữa các worker.
 */
final class CircuitBreaker
{
    public function __construct(
        private readonly Repository $cache,
        private readonly int $threshold = 5,
        private readonly int $cooldownSeconds = 60,
    ) {}

    /**
     * Thời điểm được thử lại nếu mạch đang mở; null = cho gửi.
     */
    public function openUntil(string $system): ?CarbonImmutable
    {
        $until = $this->cache->get($this->key($system, 'open_until'));
        if (! is_int($until) || $until <= now()->getTimestamp()) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp($until);
    }

    public function recordSuccess(string $system): void
    {
        $this->cache->forget($this->key($system, 'failures'));
        $this->cache->forget($this->key($system, 'open_until'));
    }

    public function recordFailure(string $system): void
    {
        $failures = (int) $this->cache->get($this->key($system, 'failures'), 0) + 1;
        $this->cache->put($this->key($system, 'failures'), $failures, now()->addHour());

        if ($failures >= $this->threshold) {
            $this->cache->put($this->key($system, 'open_until'), now()->addSeconds($this->cooldownSeconds)->getTimestamp(), now()->addSeconds($this->cooldownSeconds * 2));
            $this->cache->put($this->key($system, 'failures'), $this->threshold - 1, now()->addHour()); // lần thử sau khi mở lại mà lỗi → mở tiếp ngay
        }
    }

    private function key(string $system, string $name): string
    {
        return "vani:integration:circuit:{$system}:{$name}";
    }
}
