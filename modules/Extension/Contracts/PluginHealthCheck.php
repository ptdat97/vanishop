<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Modules\Extension\Contracts\Data\HealthStatus;

/**
 * Extension point (tag `vani.health.checks`, ADR-030 §4.G): plugin tự báo tình trạng kết nối/cấu hình.
 * Chạy theo lịch (`vani:plugin:health`, 15 phút/lần) và trong `vani:plugin:doctor`, **không** chạy trong request
 * của khách. Được gọi mạng nhưng phải có timeout ngắn; lỗi/timeout → Core ghi `error` thay plugin.
 */
interface PluginHealthCheck
{
    public const TAG = 'vani.health.checks';

    public function check(): HealthStatus;
}
