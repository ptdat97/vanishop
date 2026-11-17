<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker theo plugin cho luồng **không giao dịch** (đọc/hiển thị): `Extensions::call`, slot UI, filter
 * `on_error: skip`, cột/tab/bộ lọc Admin của plugin. Lỗi ≥ `threshold` lần trong 1 phút → bỏ qua plugin `cooldown` giây
 * trên các luồng đó, ghi log lỗi (cảnh báo vận hành) và `vani:plugin:doctor` báo `breaker_open`. Luồng giao dịch (validate,
 * action, filter `fail`) không dùng breaker: lỗi vẫn làm hỏng giao dịch để giữ bất biến.
 *
 * @see docs/05-plugin/plugin-system.md §8
 */
final class PluginBreaker
{
    /** Giữ kết quả đọc cache trong tiến trình vài giây: slot/filter gọi rất nhiều lần mỗi trang, không hỏi cache mỗi listener. */
    private const MEMO_SECONDS = 5;

    /** @var array<string, array{0: bool, 1: int}> plugin => [đang mở, hết hạn (timestamp)] */
    private array $memo = [];

    public function __construct(private readonly int $threshold = 5, private readonly int $cooldownSeconds = 300) {}

    public function isOpen(?string $plugin): bool
    {
        if ($plugin === null) {
            return false;
        }
        $now = now()->getTimestamp();
        if (isset($this->memo[$plugin]) && $this->memo[$plugin][1] > $now) {
            return $this->memo[$plugin][0];
        }
        $open = Cache::has(self::key($plugin).':open');
        $this->memo[$plugin] = [$open, $now + self::MEMO_SECONDS];

        return $open;
    }

    public function recordFailure(?string $plugin, string $where): void
    {
        if ($plugin === null) {
            return;
        }
        $key = self::key($plugin);
        Cache::add("{$key}:failures", 0, now()->addMinute());
        if (Cache::increment("{$key}:failures") >= $this->threshold) {
            Cache::put("{$key}:open", true, now()->addSeconds($this->cooldownSeconds));
            Cache::forget("{$key}:failures");
            $this->memo[$plugin] = [true, now()->getTimestamp() + self::MEMO_SECONDS];
            Log::error('Plugin lỗi liên tục — tạm bỏ qua trên luồng đọc/hiển thị.', ['plugin' => $plugin, 'where' => $where, 'cooldown_seconds' => $this->cooldownSeconds]);
        }
    }

    public function reset(string $plugin): void
    {
        Cache::forget(self::key($plugin).':open');
        Cache::forget(self::key($plugin).':failures');
        unset($this->memo[$plugin]);
    }

    public function cooldownSeconds(): int
    {
        return $this->cooldownSeconds;
    }

    private static function key(string $plugin): string
    {
        return "vani:plugin-breaker:{$plugin}";
    }
}
