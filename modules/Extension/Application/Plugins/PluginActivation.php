<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Extension\Persistence\Models\PluginScopeRecord;
use Modules\Shared\Context\CurrentContext;

/**
 * Plugin có đang được bật trong phạm vi hiện tại (CurrentContext) không.
 *
 * - Bật ở scope owner → hoạt động mọi nơi.
 * - Context có channel → bật cho channel đó hoặc cho một brand của channel.
 * - Context nhân viên cấp Owner (brandIds = null) → hoạt động nếu bật ở bất kỳ scope nào.
 */
final class PluginActivation
{
    private const CACHE_KEY = 'vani:plugins:enabled-scopes';

    /** @var Collection<int, array{plugin_id: string, scope_type: string, scope_id: int|null}>|null */
    private ?Collection $scopes = null;

    private int $version = 0;

    public function __construct(private readonly CurrentContext $context) {}

    /**
     * Bật ở ít nhất một phạm vi (dùng cho tác vụ định kỳ của plugin).
     */
    public function isEnabledAnywhere(string $pluginId): bool
    {
        return $this->enabledScopes()->contains('plugin_id', $pluginId);
    }

    /**
     * Bật ở scope owner (mọi brand) — dùng khi không xác định được brand của sự việc.
     */
    public function isActiveForOwner(string $pluginId): bool
    {
        return $this->enabledScopes()->where('plugin_id', $pluginId)->contains('scope_type', 'owner');
    }

    public function isActive(string $pluginId): bool
    {
        $rows = $this->enabledScopes()->where('plugin_id', $pluginId);

        if ($rows->isEmpty()) {
            return false;
        }

        if ($rows->contains('scope_type', 'owner')) {
            return true;
        }

        if (! $this->context->has()) {
            return false;
        }

        $scope = $this->context->scope();

        if ($scope->brandIds === null) {
            return true;
        }

        return $rows->contains(fn (array $row): bool => match ($row['scope_type']) {
            'channel' => $row['scope_id'] === $scope->channelId,
            'brand' => in_array($row['scope_id'], $scope->brandIds, true),
            default => false,
        });
    }

    public function flush(): void
    {
        $this->scopes = null;
        $this->version++;
    }

    /**
     * Phạm vi đang bật của mọi plugin enabled. Lưu trong cache dùng chung (redis/database ở production) để request/job
     * không phải truy vấn DB; PluginManager xoá cache mỗi khi trạng thái plugin đổi.
     *
     * @return Collection<int, array{plugin_id: string, scope_type: string, scope_id: int|null}>
     */
    private function enabledScopes(): Collection
    {
        if ($this->scopes !== null) {
            return $this->scopes;
        }

        try {
            $rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => PluginScopeRecord::query()
                ->where('enabled', true)
                ->whereIn('plugin_id', PluginRecord::query()->where('status', PluginStatus::Enabled)->select('id'))
                ->get(['plugin_id', 'scope_type', 'scope_id'])
                ->map(fn (PluginScopeRecord $row): array => ['plugin_id' => $row->plugin_id, 'scope_type' => $row->scope_type, 'scope_id' => $row->scope_id])
                ->all());
        } catch (QueryException) {
            // Bảng chưa có (đang cài/migrate) → coi như không plugin nào bật, không cache.
            $rows = [];
        }

        return $this->scopes = new Collection($rows);
    }

    /**
     * Gọi khi trạng thái plugin đổi (PluginManager): xoá cache dùng chung và bộ nhớ của request hiện tại.
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
}
