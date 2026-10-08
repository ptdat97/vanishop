<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Integration\Contracts\Data\IntegrationEvent;
use Modules\Integration\Contracts\IntegrationEvents;
use Modules\Integration\Persistence\Models\IntegrationEventRecord;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Payment\Contracts\Payments;
use Modules\Returns\Contracts\Returns;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Contracts\Metrics;

/**
 * Đối soát đơn ↔ event feed: bù event bị mất khi tiến trình chết giữa commit nghiệp vụ và lúc ghi feed
 * (domain event phát sau commit — xem integration-platform §11). Event bù mang `reconciled: true`.
 *
 * Trạng thái nghiệp vụ → event bắt buộc (0.3.21: không chỉ `order.*`):
 * - mọi đơn: `order.created`; confirmed/processing/completed: `order.confirmed`; cancelled: `order.cancelled`
 * - mỗi lần huỷ một phần: `order.lines_cancelled` (theo `cancellation_id`)
 * - mỗi khoản đã thu: `payment.captured` (theo `payment_id`); mỗi hoàn tiền đã xong: `payment.refunded` (theo `refund_id`)
 * - mỗi yêu cầu đổi trả: `return.created`; đã hoàn tất: `return.resolved` (theo `return_id`)
 * - mỗi vận đơn đã rời trạng thái đầu: `shipment.status_changed` có `to` = trạng thái hiện tại (theo `shipment_id`);
 *   event bù lấy `from` = trạng thái bên nhận biết gần nhất (`to` của event trước) hoặc `pending_booking`.
 *
 * Dựng lại từ trạng thái qua contract đọc — không tạo giao dịch nghiệp vụ nào; payload dựng bằng DomainEventPayloads
 * như luồng trực tiếp. Giới hạn: chỉ xét đơn có `updated_at` trong cửa sổ — trạng thái vận đơn trung gian không đổi
 * trạng thái giao hàng tổng của đơn (vd. in_transit) không làm đơn vào cửa sổ.
 */
final class OrderEventReconciler
{
    public const TYPE = 'order_events';

    private const PAGE = 200;

    private const MAX_DETAILS = 100;

    public function __construct(
        private readonly OrderReader $orders,
        private readonly IntegrationEvents $events,
        private readonly DomainEventPayloads $payloads,
        private readonly CurrentContext $context,
        private readonly Payments $payments,
        private readonly Returns $returns,
        private readonly ShipmentReader $shipments,
        private readonly Metrics $metrics,
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
                    foreach ($this->missing($order) as $expected) {
                        $discrepancies++;
                        if (count($details) < self::MAX_DETAILS) {
                            $details[] = ['order' => $order->number, 'missing' => $expected['type'], ...($expected['match'] === [] ? [] : ['entity' => $expected['match']])];
                        }
                        if ($repair) {
                            $this->events->publish(new IntegrationEvent($expected['type'], 'order', $order->number, [...($expected['data'])(), 'reconciled' => true]));
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

            $this->metrics->increment('integration.event_reconciliation_mismatch', $discrepancies);
            $this->metrics->increment('integration.event_rebuilt', $repaired);

            return ['id' => $runId, 'checked' => $checked, 'discrepancies' => $discrepancies, 'repaired' => $repaired];
        });
    }

    /**
     * @return list<array{type: string, match: array<string, string>, data: \Closure(): array<string, mixed>}>
     */
    private function missing(OrderData $order): array
    {
        return array_values(array_filter($this->expected($order), fn (array $expected): bool => ! $this->published($order, $expected['type'], $expected['match'])));
    }

    /**
     * @return list<array{type: string, match: array<string, string>, data: \Closure(): array<string, mixed>}>
     */
    private function expected(OrderData $order): array
    {
        $expected = [['type' => 'order.created', 'match' => [], 'data' => fn (): array => $this->payloads->order($order)]];
        if (in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Completed], true)) {
            $expected[] = ['type' => 'order.confirmed', 'match' => [], 'data' => fn (): array => $this->payloads->order($order)];
        }
        if ($order->status === OrderStatus::Cancelled) {
            $expected[] = ['type' => 'order.cancelled', 'match' => [], 'data' => fn (): array => $this->payloads->order($order)];
        }

        foreach ($this->orders->cancellations($order->id) as $cancellation) {
            $expected[] = ['type' => 'order.lines_cancelled', 'match' => ['cancellation_id' => $cancellation['cancellation_id']],
                'data' => fn (): array => $this->payloads->linesCancelled($order, $cancellation['cancellation_id'], $cancellation['reason'], $cancellation['amount'], $cancellation['lines'], $cancellation['promotion_clawback'])];
        }

        $settlements = $this->payments->settlementsForOrder($order->id);
        foreach ($settlements['captures'] as $capture) {
            $expected[] = ['type' => 'payment.captured', 'match' => ['payment_id' => $capture['payment_id']],
                'data' => fn (): array => $this->payloads->paymentCaptured($order, $capture['payment_id'], $capture['gateway'], $capture['amount'])];
        }
        foreach ($settlements['refunds'] as $refund) {
            $expected[] = ['type' => 'payment.refunded', 'match' => ['refund_id' => $refund['refund_id']],
                'data' => fn (): array => $this->payloads->paymentRefunded($order, $refund['refund_id'], $refund['payment_id'], $refund['amount'])];
        }

        foreach ($this->returns->forOrder($order->id) as $return) {
            $expected[] = ['type' => 'return.created', 'match' => ['return_id' => $return->publicId],
                'data' => fn (): array => $this->payloads->returnCreated($order, $return->publicId, $return->number, $return->source)];
            if ($return->status === 'resolved') {
                $expected[] = ['type' => 'return.resolved', 'match' => ['return_id' => $return->publicId],
                    'data' => fn (): array => $this->payloads->returnResolved($order, $return->publicId, $return->number, (int) $return->refundedAmount)];
            }
        }

        foreach ($this->shipments->forOrder($order->id) as $shipment) {
            if ($shipment->status === 'pending_booking') {
                continue; // trạng thái đầu: chưa có chuyển trạng thái nào để báo
            }
            $expected[] = ['type' => 'shipment.status_changed', 'match' => ['shipment_id' => $shipment->publicId, 'to' => $shipment->status],
                'data' => fn (): array => $this->payloads->shipmentStatusChanged($order, $shipment->publicId, $shipment->carrierCode, $shipment->trackingNumber, $this->lastKnownShipmentStatus($order, $shipment->publicId), $shipment->status)];
        }

        return $expected;
    }

    /**
     * @param  array<string, string>  $match  trường trong `data` nhận diện thực thể
     */
    private function published(OrderData $order, string $type, array $match): bool
    {
        $query = IntegrationEventRecord::query()->where('aggregate_type', 'order')->where('aggregate_id', $order->number)->where('event_type', $type);
        foreach ($match as $field => $value) {
            $query->where("payload->{$field}", $value);
        }

        return $query->exists();
    }

    private function lastKnownShipmentStatus(OrderData $order, string $shipmentId): string
    {
        $last = IntegrationEventRecord::query()->where('aggregate_type', 'order')->where('aggregate_id', $order->number)
            ->where('event_type', 'shipment.status_changed')->where('payload->shipment_id', $shipmentId)->orderByDesc('id')->first();

        return (string) ($last?->payload['to'] ?? 'pending_booking');
    }
}
