<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

/**
 * Giới hạn tần suất theo IP cho các route công khai. IP máy phát tải (`VANI_LOAD_TEST_IPS`, scripts/load) được bỏ qua
 * giới hạn để load test đo được năng lực thật — CHỈ ngoài production (ở production danh sách bị bỏ qua và
 * `vani:security:check` báo lỗi nếu còn đặt).
 */
final class RateLimits
{
    public static function perMinuteByIp(Request $request, int $maxAttempts, string $prefix = ''): Limit
    {
        $ip = (string) $request->ip();
        if (! app()->isProduction() && in_array($ip, (array) config('vanishop.load_test.bypass_ips'), true)) {
            return Limit::none();
        }

        return Limit::perMinute($maxAttempts)->by($prefix.$ip);
    }
}
