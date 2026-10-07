<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;

/**
 * Plugin có đang bật không. Plugin bật/tắt cho cả cửa hàng (ADR-028) — không có phạm vi brand/kênh.
 */
final class PluginActivation
{
    private const CACHE_KEY = 'vani:plugins:enabled';

    /** @var array<string, string>|null plugin id => trạng thái (enabled | draining) */
    private ?array $enabled = null;

    private int $version = 0;

    /** Đang bật hoặc đang ngừng (draining) — implementation vẫn tham gia flow. */
    public function isActive(string $pluginId): bool
    {
        return isset($this->enabledIds()[$pluginId]);
    }

    /** Đang ngừng: không nhận giao dịch mới (Extensions::acceptsNewTransactions). */
    public function isDraining(string $pluginId): bool
    {
        return ($this->enabledIds()[$pluginId] ?? null) === PluginStatus::Draining->value;
    }

    public function flush(): void
    {
        $this->enabled = null;
        $this->version++;
    }

    /**
     * Gọi khi trạng thái plugin đổi (PluginManager): xoá cache dùng chung.
     */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Tăng mỗi lần flush — ScopedExtensions dùng để biết kết quả `tagged()` đã ghi nhớ có còn đúng. */
    public function version(): int
    {
        return $this->version;
    }

    /**
     * Plugin đang bật — lưu trong cache dùng chung (redis/database ở production) để request/job không truy vấn DB.
     *
     * @return array<string, string>
     */
    private function enabledIds(): array
    {
        if ($this->enabled !== null) {
            return $this->enabled;
        }

        try {
            $ids = Cache::rememberForever(self::CACHE_KEY, fn (): array => PluginRecord::query()
                ->whereIn('status', [PluginStatus::Enabled, PluginStatus::Draining])
                ->get(['id', 'status'])
                ->mapWithKeys(fn (PluginRecord $record): array => [(string) $record->id => $record->status->value])
                ->all());
        } catch (QueryException) {
            // Bảng chưa có (đang cài/migrate) → coi như không plugin nào bật, không cache.
            $ids = [];
        }

        return $this->enabled = array_map('strval', $ids);
    }
}
