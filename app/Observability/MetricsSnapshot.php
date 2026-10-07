<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Shared\Contracts\Metrics;

/**
 * Gauge chụp định kỳ (vani:metrics:snapshot, mỗi phút) — composition root đọc số đếm (chỉ đọc) của các module.
 */
final class MetricsSnapshot
{
    public function __construct(
        private readonly Metrics $metrics,
        private readonly PluginManager $plugins,
    ) {}

    /**
     * @return array<string, int>
     */
    public function capture(): array
    {
        $values = [];
        $gauge = function (string $name, int $value, string $key = 'all') use (&$values): void {
            $this->metrics->gauge($name, $value, $key);
            $values[$key === 'all' ? $name : "{$name}:{$key}"] = $value;
        };

        $pending = DB::table('payments')->where('status', 'pending')->groupBy('gateway_code')->selectRaw('gateway_code, count(*) as total')->pluck('total', 'gateway_code');
        $gauge('payments.pending', (int) $pending->sum());
        foreach ($pending as $gateway => $total) {
            $gauge('payments.pending', (int) $total, (string) $gateway);
        }
        $gauge('orders.pending', DB::table('orders')->where('order_status', 'pending')->count());

        $gauge('integration.outbox_backlog', DB::table('integration_outbox')->whereIn('status', ['pending', 'processing'])->count());
        $gauge('integration.outbox_failed', DB::table('integration_outbox')->whereIn('status', ['failed', 'dead'])->count());
        $gauge('integration.inbox_failed', DB::table('integration_inbox')->whereIn('status', ['failed', 'dead'])->count());

        $gauge('inventory.reconciliation_open', DB::table('inventory_reconciliation_lines')->whereNull('resolved_at')->count());
        if (Schema::hasTable('payment_reconciliation_lines')) {
            $gauge('payments.reconciliation_open', DB::table('payment_reconciliation_lines')->whereNull('resolved_at')->count());
        }

        foreach (PluginRecord::query()->whereIn('status', [PluginStatus::Enabled, PluginStatus::Draining])->pluck('id') as $pluginId) {
            $blockers = count($this->plugins->inUse((string) $pluginId));
            if ($blockers > 0) {
                $gauge('plugin.active_transactions', $blockers, (string) $pluginId);
            }
        }

        return $values;
    }
}
