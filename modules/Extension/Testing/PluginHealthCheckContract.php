<?php

declare(strict_types=1);

namespace Modules\Extension\Testing;

use Closure;
use Modules\Extension\Contracts\Data\HealthStatus;
use Modules\Extension\Contracts\PluginHealthCheck;

/**
 * Contract test cho PluginHealthCheck. Kiểm tra: trả `HealthStatus` với trạng thái hợp lệ, không ném lỗi; không ok thì có
 * thông điệp cho nhân viên.
 */
final class PluginHealthCheckContract
{
    /**
     * @param  Closure(): PluginHealthCheck  $check
     */
    public static function define(string $label, Closure $check): void
    {
        describe("PluginHealthCheck contract: {$label}", function () use ($check): void {
            it('trả HealthStatus hợp lệ; không ok thì có thông điệp', function () use ($check): void {
                $status = $check()->check();
                expect($status)->toBeInstanceOf(HealthStatus::class)
                    ->and($status->status)->toBeIn([HealthStatus::OK, HealthStatus::WARNING, HealthStatus::ERROR]);
                if ($status->status !== HealthStatus::OK) {
                    expect($status->message)->not->toBe('');
                }
            });
        });
    }
}
