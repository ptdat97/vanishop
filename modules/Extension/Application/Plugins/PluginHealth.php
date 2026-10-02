<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Support\Facades\Cache;
use Modules\Extension\Contracts\Data\HealthStatus;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\PluginHealthCheck;
use Throwable;

/**
 * Chạy PluginHealthCheck của các plugin đang bật; lưu kết quả gần nhất vào cache dùng chung cho Admin.
 */
final class PluginHealth
{
    private const CACHE_KEY = 'vani:plugins:health';

    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @return array<string, array{status: string, message: string, checked_at: string}> plugin id => kết quả
     */
    public function run(): array
    {
        $results = [];
        foreach ($this->extensions->tagged(PluginHealthCheck::TAG) as $check) {
            if (! $check instanceof PluginHealthCheck) {
                continue;
            }

            $plugin = $this->extensions->ownerOf($check) ?? 'core';
            try {
                $status = $check->check();
            } catch (Throwable $exception) {
                report($exception);
                $status = HealthStatus::error('Kiểm tra lỗi: '.$exception->getMessage());
            }

            $previous = $results[$plugin] ?? null;
            // Một plugin nhiều kiểm tra: giữ kết quả nặng nhất.
            if ($previous === null || self::rank($status->status) > self::rank($previous['status'])) {
                $results[$plugin] = ['status' => $status->status, 'message' => $status->message, 'checked_at' => now()->toIso8601String()];
            }
        }

        Cache::forever(self::CACHE_KEY, $results);

        return $results;
    }

    /**
     * @return array<string, array{status: string, message: string, checked_at: string}>
     */
    public function last(): array
    {
        return (array) Cache::get(self::CACHE_KEY, []);
    }

    private static function rank(string $status): int
    {
        return ['ok' => 0, 'warning' => 1, 'error' => 2][$status] ?? 2;
    }
}
