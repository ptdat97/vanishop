<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Facades\Hook;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Returns\Contracts\Data\ReturnView;
use Modules\Returns\Contracts\ReturnRejected;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Domain\ReturnProgress;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnAttachment;
use Modules\Returns\Persistence\Models\ReturnLine;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\StoreClock;

/**
 * Phần dùng chung của Returns: chuyển trạng thái theo máy trạng thái của Domain + sổ `return_events`, đồng bộ
 * `return_status` của đơn (`ReturnProgress`), số đã giao/đang trả theo dòng, hook chặn duyệt, view cho API/Admin.
 */
final class ReturnRecords
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly ShipmentReader $shipments,
        private readonly CurrentContext $context,
        private readonly VariantDirectory $variants,
    ) {}

    public function view(ReturnRequest $return): ReturnView
    {
        $orderLines = collect($this->orders->lines($return->order_id))->keyBy('id');
        $attachments = ReturnAttachment::query()->where('return_request_id', $return->id)->orderBy('id')->get();
        $exchangeVariants = $return->resolution === 'exchange' ? $this->variants->find($return->lines()->pluck('exchange_variant_id')->filter()->unique()->values()->all()) : [];

        return vani_filter('vani.service.returns.view', new ReturnView(
            id: $return->id,
            publicId: $return->public_id,
            number: $return->number,
            orderId: $return->order_id,
            status: $return->status->value,
            reasonCode: $return->reason_code,
            customerNote: $return->customer_note,
            source: (string) $return->source,
            refundAmount: $return->refund_amount,
            refundedAmount: $return->refunded_amount,
            currencyCode: $return->currency_code,
            createdAt: (string) StoreClock::iso($return->created_at),
            lines: $return->lines()->get()->map(fn (ReturnLine $line): array => [
                'id' => $line->id, 'order_line_id' => $line->order_line_id, 'sku' => $orderLines[$line->order_line_id]->sku ?? '',
                'name' => $orderLines[$line->order_line_id]->productName ?? '', 'quantity' => $line->quantity,
                'refund_amount' => $line->refund_amount, 'condition' => $line->condition,
                'exchange_variant_id' => $line->exchange_variant_id, 'exchange_sku' => $exchangeVariants[$line->exchange_variant_id ?? 0]->sku ?? null,
            ])->all(),
            events: DB::table('return_events')->where('return_request_id', $return->id)->orderBy('id')->get()->map(fn (object $event): array => [
                'from' => $event->from_status, 'to' => $event->to_status, 'note' => $event->note, 'source' => $event->source,
                'at' => StoreClock::format((string) $event->created_at),
            ])->all(),
            lockVersion: $return->lock_version,
            resolution: (string) $return->resolution,
            replacementOrderId: $return->replacement_order_id,
            returnTrackingNumber: $return->return_tracking_number,
            attachments: $attachments->map(fn (ReturnAttachment $attachment): array => [
                'id' => $attachment->id,
                'url' => URL::temporarySignedRoute('returns.attachment', now()->addMinutes(30), ['attachment' => $attachment->id]),
            ])->all(),
            acceptsAttachments: $return->status->acceptsAttachments() && $attachments->count() < Returns::MAX_ATTACHMENTS,
        ), $return);
    }

    public function move(ReturnRequest $return, ReturnStatus $to, ?string $note, string $source): void
    {
        $from = $return->status;
        if (! $from->canMoveTo($to)) {
            throw ReturnRejected::invalidTransition($from->value, $to->value);
        }

        $return->update(['status' => $to, 'lock_version' => $return->lock_version + 1]);
        $this->event($return, $from, $to, $note, $source);
    }

    public function event(ReturnRequest $return, ?ReturnStatus $from, ReturnStatus $to, ?string $note, string $source): void
    {
        $actor = $this->context->has() ? $this->context->actor() : null;
        DB::table('return_events')->insert([
            'return_request_id' => $return->id, 'from_status' => $from?->value, 'to_status' => $to->value,
            'note' => $note === null ? null : mb_substr($note, 0, 255), 'actor_type' => $actor?->type->value, 'actor_id' => $actor?->id,
            'source' => $source, 'created_at' => now(),
        ]);
    }

    /**
     * return_status của đơn: có yêu cầu mở → requested/in_progress; không thì theo số lượng đã trả xong.
     */
    public function syncOrder(int $orderId): void
    {
        $returns = ReturnRequest::query()->with('lines')->where('order_id', $orderId)->get();
        [$delivered] = $this->delivered($orderId);
        $resolvedQuantity = $returns->where('status', ReturnStatus::Resolved)->sum(fn (ReturnRequest $return): int => $return->lines->sum('quantity'));
        $status = ReturnProgress::forOrder($returns->map(fn (ReturnRequest $return): ReturnStatus => $return->status)->values()->all(), (int) $resolvedQuantity, array_sum($delivered));

        $this->transitions->setReturnStatus($orderId, $status, 'returns_changed', 'system');
    }

    /**
     * @return array<int, int> order_line_id => số lượng đang/đã trả (yêu cầu mở hoặc đã hoàn tất)
     */
    public function takenQuantities(int $orderId): array
    {
        $taken = [];
        foreach (ReturnRequest::query()->with('lines')->where('order_id', $orderId)->get() as $return) {
            if (! $return->status->countsTowardsLimit()) {
                continue;
            }
            foreach ($return->lines as $line) {
                $taken[$line->order_line_id] = ($taken[$line->order_line_id] ?? 0) + $line->quantity;
            }
        }

        return $taken;
    }

    /**
     * @return array{0: array<int, int>, 1: ?DateTimeImmutable} số đã giao theo dòng + thời điểm giao gần nhất
     */
    public function delivered(int $orderId): array
    {
        $quantities = [];
        $latest = null;
        foreach ($this->shipments->forOrder($orderId) as $shipment) {
            if ($shipment->status !== 'delivered') {
                continue;
            }
            foreach ($shipment->lines as $line) {
                $quantities[$line['order_line_id']] = ($quantities[$line['order_line_id']] ?? 0) + $line['quantity'];
            }
            $at = $shipment->deliveredAt === null ? null : new DateTimeImmutable($shipment->deliveredAt);
            $latest = $latest === null || ($at !== null && $at > $latest) ? $at : $latest;
        }

        return [$quantities, $latest];
    }

    /**
     * @return array<int, int> order_line_id => location của vận đơn đã giao dòng đó
     */
    public function shipmentLocations(int $orderId): array
    {
        $locations = [];
        foreach ($this->shipments->forOrder($orderId) as $shipment) {
            if ($shipment->status === 'delivered') {
                foreach ($shipment->lines as $line) {
                    $locations[$line['order_line_id']] ??= $shipment->locationId;
                }
            }
        }

        return $locations;
    }

    /**
     * Lý do chưa được duyệt (hook `vani.returns.before_approve`, chốt ③ ADR-046, EP v3 X2) — vd. khách chưa gửi ảnh.
     *
     * @return list<string>
     */
    public function approveBlockers(ReturnRequest $return): array
    {
        return array_values(array_filter(Hook::collect('vani.returns.before_approve', $this->view($return->refresh())), fn ($message): bool => is_string($message) && $message !== ''));
    }
}
