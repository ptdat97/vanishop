<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use App\Observability\PulseMetrics;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Livewire\Concerns\HasPeriod;
use Laravel\Pulse\Livewire\Concerns\RemembersQueries;
use Livewire\Attributes\Lazy;

/**
 * Thẻ Pulse "Thương mại": counter trong khoảng thời gian đang chọn + gauge mới nhất (Phase 6 observability).
 */
#[Lazy]
final class CommerceMetricsCard extends Card
{
    use HasPeriod, RemembersQueries;

    /** Counter: tên metric => nhãn. Cảnh báo (đỏ) khi > 0 với nhóm lỗi. */
    public const COUNTERS = [
        'orders.created' => 'Đơn tạo', 'orders.failed' => 'Checkout bị từ chối', 'orders.cancelled' => 'Đơn huỷ',
        'payments.captured' => 'Thanh toán thu', 'payments.failed' => 'Thanh toán thất bại', 'payments.refunded' => 'Hoàn tiền',
        'inventory.reservation_failed' => 'Giữ hàng thất bại', 'shipments.delivered' => 'Giao thành công', 'shipments.returned' => 'Hàng hoàn',
        'integration.delivery_failed' => 'Gửi tích hợp lỗi', 'integration.event_replay' => 'Replay', 'integration.event_rebuilt' => 'Event bù',
        'payments.reconciliation_mismatch' => 'Lệch thanh toán ↔ cổng', 'inventory.reconciliation_mismatch' => 'Lệch tồn',
        'integration.event_reconciliation_mismatch' => 'Lệch event',
    ];

    public const ALERTS = [
        'orders.failed', 'payments.failed', 'inventory.reservation_failed', 'integration.delivery_failed',
        'payments.reconciliation_mismatch', 'inventory.reconciliation_mismatch', 'integration.event_reconciliation_mismatch',
    ];

    /** Gauge: tên => nhãn. */
    public const GAUGES = [
        'payments.pending' => 'Thanh toán đang chờ', 'orders.pending' => 'Đơn chờ xác nhận', 'integration.outbox_backlog' => 'Outbox tồn',
        'integration.outbox_failed' => 'Outbox lỗi/dead', 'integration.inbox_failed' => 'Inbox lỗi/dead',
        'inventory.reconciliation_open' => 'Lệch tồn chưa xử lý', 'payments.reconciliation_open' => 'Lệch thanh toán chưa xử lý',
        'plugin.active_transactions' => 'Plugin còn giao dịch dở',
    ];

    public function render(): Renderable
    {
        [$counters, $time, $runAt] = $this->remember(function (): array {
            $types = array_map(fn (string $name): string => PulseMetrics::PREFIX.$name, array_keys(self::COUNTERS));
            $totals = $this->aggregateTotal($types, 'sum');

            return array_map(fn (string $name): int => (int) ($totals[PulseMetrics::PREFIX.$name] ?? 0), array_combine(array_keys(self::COUNTERS), array_keys(self::COUNTERS)));
        }, 'counters');

        $gauges = [];
        foreach (array_keys(self::GAUGES) as $name) {
            $values = $this->values(PulseMetrics::PREFIX.$name);
            $gauges[$name] = $name === 'plugin.active_transactions'
                ? $values->map(fn ($value): string => $value->key.': '.$value->value)->values()->implode(', ')
                : (string) ($values['all']->value ?? '—');
        }

        return View::make('livewire.pulse.commerce-metrics', [
            'counters' => $counters, 'gauges' => $gauges, 'time' => $time, 'runAt' => $runAt,
            'counterLabels' => self::COUNTERS, 'gaugeLabels' => self::GAUGES, 'alerts' => self::ALERTS,
        ]);
    }
}
