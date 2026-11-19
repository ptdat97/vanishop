<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Persistence\Models\ShipmentLine;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreClock;

/**
 * Thẻ "Giao hàng" trên trang đơn Admin (slot vani.admin.order.sidebar) — 0.3.96: thao tác ngay trên trang đơn theo trạng thái
 * vận đơn (tạo vận đơn → nhập mã + hãng → đã bàn giao → đã giao / giao không thành công → hoàn về). Hãng nhập tay chỉ có các
 * bước nhân viên thực sự biết; bước trung gian (đang vận chuyển, đang giao) chỉ đến từ hãng có kết nối. Sự kiện vận đơn gộp vào
 * lịch sử đơn (slot vani.admin.order.timeline).
 */
final class OrderShipmentPanel
{
    public function __construct(
        private readonly CarrierRegistry $carriers,
        private readonly MoneyFormatter $money,
        private readonly VariantDirectory $variants,
    ) {}

    /**
     * Thông tin giao hàng là một phần của đơn: ai xem được đơn đều thấy; thao tác cần `fulfillment.manage`.
     *
     * @return array{title: string, rows: list<array{label: string, value: string}>, link: array{label: string, url: string}, actions: list<array<string, mixed>>}
     */
    public function __invoke(OrderDetail $order): array
    {
        $labels = (array) __('fulfillment::labels.shipment_status');
        $manage = Gate::allows('fulfillment.manage');
        $base = route('admin.fulfillment.shipments.index');
        $shipments = Shipment::query()->where('order_id', $order->id)->orderBy('id')->get();
        $active = $shipments->filter(fn (Shipment $shipment): bool => $shipment->status !== ShipmentStatus::Cancelled);

        $rows = [];
        $actions = [];
        foreach ($shipments as $index => $shipment) {
            $carrier = $this->carriers->carrier($shipment->carrier_code);
            $name = $shipment->service_code ?: ($carrier?->label() ?? $shipment->carrier_code);
            $prefix = $shipments->count() > 1 ? __('fulfillment::texts.order_shipment_panel.shipment').($index + 1).': ' : '';
            $rows[] = ['label' => $prefix.$name, 'value' => ($shipment->tracking_number ?? __('fulfillment::texts.order_shipment_panel.no_code_yet')).' · '.($labels[$shipment->status->value] ?? $shipment->status->value)];
            if ($shipment->cod_amount > 0) {
                $rows[] = ['label' => __('fulfillment::texts.order_shipment_panel.cod_amount'), 'value' => $this->money->formatAmount($shipment->cod_amount, $shipment->currency_code)];
            }
            if ($shipment->last_error !== null && $shipment->status === ShipmentStatus::BookingFailed) {
                $rows[] = ['label' => __('fulfillment::texts.order_shipment_panel.error'), 'value' => $shipment->last_error];
            }
            if ($manage) {
                array_push($actions, ...$this->actions($shipment, $base, $prefix, ! ($carrier?->capabilities()->autoBooking ?? false)));
            }
        }

        if ($manage && $active->isEmpty() && in_array($order->orderStatus, ['confirmed', 'processing'], true)) {
            $actions[] = ['key' => 'create', 'label' => __('fulfillment::texts.order_shipment_panel.create_shipment'), 'url' => $base, 'data' => ['order_id' => $order->id], 'primary' => true, 'tone' => 'primary'];
        }

        return [
            'title' => __('fulfillment::texts.order_shipment_panel.fulfilment'),
            'rows' => $rows === [] ? [['label' => __('fulfillment::texts.order_shipment_panel.no_shipments_yet'), 'value' => '']] : $rows,
            'link' => ['label' => __('fulfillment::texts.order_shipment_panel.open_shipment_page'), 'url' => route('admin.fulfillment.shipments.index', ['order' => $order->number])],
            'actions' => $actions,
        ];
    }

    /**
     * @return list<array{ts: int, at: string, title: string, detail: string|null, source: string}>
     */
    public function timeline(OrderDetail $order): array
    {
        $labels = (array) __('fulfillment::labels.shipment_status');

        return DB::table('shipment_events')->join('shipments', 'shipments.id', '=', 'shipment_events.shipment_id')
            ->where('shipments.order_id', $order->id)
            ->orderBy('shipment_events.id')
            ->get(['shipment_events.status', 'shipment_events.source', 'shipment_events.description', 'shipment_events.occurred_at', 'shipments.tracking_number'])
            ->map(fn (object $event): array => [
                'ts' => strtotime((string) $event->occurred_at.' UTC') ?: 0,
                'at' => (string) StoreClock::format((string) $event->occurred_at),
                'title' => __('fulfillment::texts.order_shipment_panel.shipment_2').($labels[$event->status] ?? $event->status),
                'detail' => trim(($event->tracking_number ?? '').' '.($event->description ?? '')) ?: null,
                'source' => (string) $event->source,
            ])->all();
    }

    /**
     * Thao tác theo trạng thái vận đơn; nút `primary` là bước tiếp theo của đơn.
     *
     * @return list<array<string, mixed>>
     */
    private function actions(Shipment $shipment, string $base, string $prefix, bool $manual): array
    {
        $url = "{$base}/{$shipment->id}";
        $status = fn (ShipmentStatus $to, string $label, bool $primary = false, string $tone = 'secondary'): array => [
            'key' => "{$shipment->id}-{$to->value}", 'label' => $prefix.$label, 'url' => "{$url}/status", 'data' => ['status' => $to->value],
            'primary' => $primary, 'tone' => $tone, 'confirm' => "{$label}?",
        ];
        $cancel = ['key' => "{$shipment->id}-cancel", 'label' => $prefix.__('fulfillment::texts.order_shipment_panel.cancel_shipment'), 'url' => "{$url}/cancel", 'tone' => 'danger',
            'fields' => [['key' => 'reason', 'label' => __('fulfillment::texts.order_shipment_panel.reason_cancelling_shipment'), 'required' => true]]];

        return match (true) {
            in_array($shipment->status, [ShipmentStatus::PendingBooking, ShipmentStatus::BookingFailed], true) => [
                ...$this->splitAction($shipment, $url, $prefix),
                ...($manual ? [['key' => "{$shipment->id}-book", 'label' => $prefix.__('fulfillment::texts.order_shipment_panel.enter_tracking_number'), 'url' => "{$url}/book", 'primary' => true, 'tone' => 'primary', 'fields' => [
                    ['key' => 'tracking_number', 'label' => __('fulfillment::texts.order_shipment_panel.tracking_number'), 'required' => true],
                    ['key' => 'service_code', 'label' => __('fulfillment::texts.order_shipment_panel.carrier'), 'placeholder' => 'vd. GHN, J&T, Viettel Post'],
                ]]] : [['key' => "{$shipment->id}-push", 'label' => $prefix.($shipment->status === ShipmentStatus::BookingFailed ? __('fulfillment::texts.order_shipment_panel.hand_carrier_again') : __('fulfillment::texts.order_shipment_panel.hand_carrier')), 'url' => "{$url}/push", 'primary' => true, 'tone' => 'primary',
                    'confirm' => __('fulfillment::texts.order_shipment_panel.finished_packing_hand_shipment_carrier')]]),
                $cancel,
            ],
            $shipment->status === ShipmentStatus::Created => [$status(ShipmentStatus::PickedUp, __('fulfillment::texts.order_shipment_panel.handed_over_carrier'), true, 'primary'), $cancel],
            in_array($shipment->status, [ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, ShipmentStatus::FailedAttempt], true) => [
                $status(ShipmentStatus::Delivered, __('fulfillment::texts.order_shipment_panel.delivered'), true, 'primary'),
                ...($shipment->status !== ShipmentStatus::FailedAttempt ? [$status(ShipmentStatus::FailedAttempt, __('fulfillment::texts.order_shipment_panel.delivery_failed'))] : []),
                $status(ShipmentStatus::Returning, __('fulfillment::texts.order_shipment_panel.returning_sender'), false, 'danger'),
            ],
            $shipment->status === ShipmentStatus::Returning => [$status(ShipmentStatus::Returned, __('fulfillment::texts.order_shipment_panel.return_received'), true, 'primary')],
            default => [],
        };
    }

    /**
     * "Giao trước phần có hàng" (L37): vận đơn chờ đặt có từ 2 món — mỗi dòng một ô "số giao sau" (mặc định 0).
     *
     * @return list<array<string, mixed>>
     */
    private function splitAction(Shipment $shipment, string $url, string $prefix): array
    {
        $lines = ShipmentLine::query()->where('shipment_id', $shipment->id)->orderBy('id')->get();
        if ((int) $lines->sum('quantity') < 2 || $shipment->booking_lease !== null) {
            return [];
        }
        $skus = $this->variants->find($lines->pluck('variant_id')->map(fn (mixed $id): int => (int) $id)->all());

        return [[
            'key' => "{$shipment->id}-split", 'label' => $prefix.__('fulfillment::texts.order_shipment_panel.ship_available_items_first'), 'url' => "{$url}/split", 'tone' => 'secondary',
            'fields' => $lines->map(fn (ShipmentLine $line): array => [
                'key' => "move_{$line->order_line_id}",
                'label' => 'Giao sau — '.($skus[$line->variant_id]->sku ?? "#{$line->variant_id}").__('fulfillment::texts.order_shipment_panel.quantity', ['quantity' => $line->quantity]),
                'value' => '0',
            ])->values()->all(),
        ]];
    }
}
