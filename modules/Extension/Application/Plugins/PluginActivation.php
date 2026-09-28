<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
    /** @var Collection<int, PluginScopeRecord>|null */
    private ?Collection $scopes = null;

    public function __construct(private readonly CurrentContext $context) {}

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

        return $rows->contains(fn (PluginScopeRecord $row): bool => match ($row->scope_type) {
            'channel' => $row->scope_id === $scope->channelId,
            'brand' => in_array($row->scope_id, $scope->brandIds, true),
            default => false,
        });
    }

    public function flush(): void
    {
        $this->scopes = null;
    }

    /**
     * @return Collection<int, PluginScopeRecord>
     */
    private function enabledScopes(): Collection
    {
        if ($this->scopes !== null) {
            return $this->scopes;
        }

        if (! Schema::hasTable('plugin_scopes')) {
            return $this->scopes = new Collection;
        }

        $enabledPlugins = PluginRecord::query()->where('status', PluginStatus::Enabled)->pluck('id');

        return $this->scopes = PluginScopeRecord::query()
            ->where('enabled', true)
            ->whereIn('plugin_id', $enabledPlugins)
            ->get();
    }
}
