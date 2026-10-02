<?php

declare(strict_types=1);

namespace Plugin\ZaloZns\Infrastructure;

use Modules\Extension\Contracts\Data\HealthStatus;
use Modules\Extension\Contracts\PluginHealthCheck;

/**
 * Kiểm tra cấu hình Zalo ZNS (không gọi mạng): thiếu app/refresh token → không gửi được; chế độ thử → không tới khách.
 */
final class ZnsHealthCheck implements PluginHealthCheck
{
    public function check(): HealthStatus
    {
        if ((string) config('vani.zalo-zns.app_id') === '' || (string) config('vani.zalo-zns.app_secret') === '') {
            return HealthStatus::error('Chưa cấu hình ZALO_APP_ID/ZALO_APP_SECRET — không gửi được ZNS.');
        }
        if ((string) config('vani.zalo-zns.refresh_token') === '') {
            return HealthStatus::error('Chưa có ZALO_REFRESH_TOKEN — cần cấp quyền OA lần đầu.');
        }
        if ((string) config('vani.zalo-zns.mode', 'production') !== 'production') {
            return HealthStatus::warning('ZNS đang ở chế độ thử (ZALO_ZNS_MODE) — tin chỉ tới số thử nghiệm.');
        }

        return HealthStatus::ok();
    }
}
