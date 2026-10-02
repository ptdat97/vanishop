<?php

declare(strict_types=1);

namespace Plugin\ProvincesVn\Infrastructure;

use Modules\Extension\Contracts\Data\HealthStatus;
use Modules\Extension\Contracts\PluginHealthCheck;
use Throwable;

final class DivisionsHealthCheck implements PluginHealthCheck
{
    public function __construct(private readonly VnDivisions $divisions) {}

    public function check(): HealthStatus
    {
        try {
            $count = $this->divisions->count();
        } catch (Throwable $exception) {
            return HealthStatus::error($exception->getMessage());
        }

        return $count === 34 ? HealthStatus::ok("{$count} tỉnh/thành") : HealthStatus::warning("Dữ liệu có {$count} tỉnh/thành (mong đợi 34 sau sắp xếp 07/2025).");
    }
}
