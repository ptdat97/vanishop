<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Integration\Contracts\Data\IntegrationEvent;
use Modules\Integration\Contracts\IntegrationEvents;
use Modules\Integration\Persistence\Models\IntegrationEventRecord;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Đối soát đơn ↔ event feed: bù event bị mất khi tiến trình chết giữa commit nghiệp vụ và lúc ghi feed
 * (domain event phát sau commit — xem integration-platform §11). Event bù mang `reconciled: true`.
 *
 * Trạng thái đơn → event bắt buộc:
 * - mọi đơn: `order.created`
 * - confirmed/processing/completed: `order.confirmed`
 * - cancelled: `order.cancelled` (không suy ra được đơn huỷ đã từng xác nhận hay chưa)
 */
final class OrderEventReconciler
{
    public const TYPE = 'order_events';

    private const PAGE = 200;

    private const MAX_DETAILS = 100;

    public function __construct(
        private readonly OrderReader $orders,
        private readonly IntegrationEvents $events,
        private readonly CanonicalPayloads $payloads,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  bool  $repair  false = chỉ báo cáo
     * @return array{id: int, checked: int, discrepancies: int, repaired: int}
     */
    public function run(CarbonImmutable $from, CarbonImmutable $to, bool $repair = true): array
    {
        return $this->context->runAs(ContextScope::system('integration reconcile orders'), function () use ($from, $to, $repair): array {
            $runId = (int) DB::table('integration_reconciliations')->insertGetId([
                'type' => self::TYPE, 'window_from' => $from, 'window_to' => $to, 'started_at' => now(),
            ]);

            $checked = $discrepancies = $repaired = 0;
            $details = [];
            $since = $from;
            $afterId = 0;

            while (true) {
                $page = $this->orders->changedSince($since, $afterId, self::PAGE);
                foreach ($page as $order) {
                    if (CarbonImmutable::parse((string) $order->updatedAt)->gt($to)) {
                        break 2;
                    }

                    $checked++;
                    foreach ($this->missing($order) as $type) {
                        $discrepancies++;
                        if (count($details) < self::MAX_DETAILS) {
                            $details[] = ['order' => $order->number, 'missing' => $type];
                        }
                        if ($repair) {
                            $this->events->publish(new IntegrationEvent($type, 'order', $order->number, [
                                'order' => $this->payloads->order($order), 'reconciled' => true,
                            ]));
                            $repaired++;
                        }
                    }
                }

                if (count($page) < self::PAGE) {
                    break;
                }
                $last = $page[array_key_last($page)];
                $since = CarbonImmutable::parse((string) $last->updatedAt);
                $afterId = $last->id;
            }

            DB::table('integration_reconciliations')->where('id', $runId)->update([
                'checked' => $checked, 'discrepancies' => $discrepancies, 'repaired' => $repaired,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE), 'finished_at' => now(),
            ]);

            if ($discrepancies > 0) {
                Log::warning('Đối soát event đơn hàng có chênh lệch.', ['run_id' => $runId, 'discrepancies' => $discrepancies, 'repaired' => $repaired]);
            }

            return ['id' => $runId, 'checked' => $checked, 'discrepancies' => $discrepancies, 'repaired' => $repaired];
        });
    }

    /**
     * @return list<string>
     */
    private function missing(OrderData $order): array
    {
        $expected = ['order.created'];
        if (in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Completed], true)) {
            $expected[] = 'order.confirmed';
        }
        if ($order->status === OrderStatus::Cancelled) {
            $expected[] = 'order.cancelled';
        }

        $present = IntegrationEventRecord::query()
            ->where('aggregate_type', 'order')
            ->where('aggregate_id', $order->number)
            ->whereIn('event_type', $expected)
            ->distinct()
            ->pluck('event_type')
            ->all();

        return array_values(array_diff($expected, $present));
    }
}
