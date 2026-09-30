<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Fulfillment\Contracts\Data\AllocationProposal;
use Modules\Fulfillment\Contracts\Data\ShipmentData;
use Modules\Fulfillment\Contracts\Data\SourcingRequest;
use Modules\Fulfillment\Contracts\FulfillmentRejected;
use Modules\Fulfillment\Domain\FulfillmentProgress;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Events\ShipmentCreated;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Persistence\Models\ShipmentLine;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Contracts\Data\ReservedLine;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Ordering\Contracts\Data\OrderLineData;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Shared\Domain\Money\Money;
use Throwable;

/**
 * docs/09-order/fulfillment.md §3 (chế độ internal). Mọi thay đổi shipment: khoá dòng shipment → ghi shipment_events
 * (unique chống trùng) → không cho lùi trạng thái → tính lại fulfillment_status của đơn → commit giữ hàng khi mọi
 * shipment đã rời kho → nhập lại kho khi hàng hoàn về.
 */
final class FulfillmentService
{
    public function __construct(
        private readonly CarrierRegistry $registry,
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly InventoryReservation $inventory,
        private readonly InventoryReturns $returns,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Tạo shipment cho đơn đã xác nhận theo SourcingStrategy. Trả các shipment id vừa tạo.
     *
     * @return list<int>
     */
    public function createForOrder(int $orderId, ?string $carrierCode = null): array
    {
        $carrierCode ??= (string) config('vanishop.fulfillment.default_carrier', 'manual');
        $carrier = $this->registry->carrier($carrierCode) ?? throw new \InvalidArgumentException("Carrier [{$carrierCode}] chưa đăng ký.");

        $ids = DB::transaction(function () use ($orderId, $carrierCode): array {
            $order = $this->orders->find($orderId) ?? throw FulfillmentRejected::orderNotReady('missing');
            if (! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing], true)) {
                throw FulfillmentRejected::orderNotReady($order->status->value);
            }
            if (Shipment::query()->where('order_id', $orderId)->where('status', '!=', ShipmentStatus::Cancelled)->lockForUpdate()->exists()) {
                throw FulfillmentRejected::alreadyHasShipments();
            }

            $lines = $this->orders->lines($orderId);
            $reserved = $this->inventory->reservedLines($order->reservationKey);
            $strategy = $this->registry->sourcing($order->brandId)
                ?? throw new \InvalidArgumentException('SourcingStrategy chưa đăng ký.');
            $proposals = $strategy->allocate(new SourcingRequest($orderId, $order->brandId, $lines, $reserved, $order->shippingAddress));
            $this->assertMatchesReservation($proposals, $lines, $reserved);

            $ids = [];
            // Thu hộ: cổng thu tiền khi giao đặt đơn ở `cod_pending` (GatewayCapabilities::collectsOnDelivery).
            $cod = $order->paymentStatus === 'cod_pending' ? $order->totalAmount : 0;
            foreach ($proposals as $index => $proposal) {
                $shipment = Shipment::query()->create([
                    'public_id' => (string) Str::ulid(), 'order_id' => $orderId, 'brand_id' => $order->brandId, 'location_id' => $proposal->locationId,
                    'carrier_code' => $carrierCode, 'cod_amount' => $index === 0 ? $cod : 0, 'currency_code' => $order->currencyCode,
                    'status' => ShipmentStatus::PendingBooking,
                ]);
                $variantByLine = array_column(array_map(fn ($line): array => ['id' => $line->id, 'variant' => $line->variantId], $lines), 'variant', 'id');
                foreach ($proposal->lines as $lineId => $quantity) {
                    ShipmentLine::query()->create(['shipment_id' => $shipment->id, 'order_line_id' => $lineId, 'variant_id' => $variantByLine[$lineId], 'quantity' => $quantity]);
                }
                $this->recordEvent($shipment, ShipmentStatus::PendingBooking, "created:{$shipment->public_id}", 'system', null, []);
                event(new ShipmentCreated($shipment->id, $orderId, $carrierCode, $shipment->brand_id));
                $ids[] = $shipment->id;
            }

            if ($order->status === OrderStatus::Confirmed) {
                $this->transitions->transition($orderId, OrderStatus::Processing, 'shipments_created', 'system');
            }
            $this->syncOrder($orderId);

            return $ids;
        });

        if ($carrier->capabilities()->autoBooking) {
            foreach ($ids as $id) {
                BookShipmentJob::dispatch($id)->afterCommit();
            }
        }

        return $ids;
    }

    /**
     * Ghi nhận mã vận đơn (nhân viên nhập với carrier thủ công, hoặc kết quả đặt tự động).
     */
    public function book(int $shipmentId, ?string $trackingNumber, ?string $serviceCode = null, ?string $labelUrl = null): void
    {
        DB::transaction(function () use ($shipmentId, $trackingNumber, $serviceCode, $labelUrl): void {
            $shipment = Shipment::query()->whereKey($shipmentId)->lockForUpdate()->firstOrFail();
            if (! in_array($shipment->status, [ShipmentStatus::PendingBooking, ShipmentStatus::BookingFailed], true)) {
                throw FulfillmentRejected::invalidTransition($shipment->status->value, ShipmentStatus::Created->value);
            }

            $carrier = $this->registry->carrier($shipment->carrier_code);
            $booked = $carrier?->createShipment($this->data($shipment, $trackingNumber, $serviceCode));

            try {
                $shipment->update([
                    'tracking_number' => $booked->trackingNumber ?? $trackingNumber, 'service_code' => $booked->serviceCode ?? $serviceCode,
                    'label_url' => $booked->labelUrl ?? $labelUrl, 'last_error' => null,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw FulfillmentRejected::trackingTaken();
            }

            $this->apply($shipment, ShipmentStatus::Created, "booked:{$shipment->public_id}", 'system', null, [], strict: true);
        });
    }

    /**
     * Job: đặt vận đơn qua API hãng. Lỗi quá số lần thử → booking_failed (nhân viên đặt lại hoặc chuyển thủ công).
     */
    public function bookAutomatically(int $shipmentId, int $maxAttempts): void
    {
        $shipment = Shipment::query()->find($shipmentId);
        if ($shipment === null || ! in_array($shipment->status, [ShipmentStatus::PendingBooking, ShipmentStatus::BookingFailed], true)) {
            return;
        }

        $shipment->increment('booking_attempts');
        try {
            $this->book($shipmentId, null);
        } catch (Throwable $exception) {
            Log::warning('Đặt vận đơn với hãng lỗi.', ['shipment' => $shipment->public_id, 'carrier' => $shipment->carrier_code, 'exception' => $exception]);
            $shipment->refresh();
            $shipment->update(['last_error' => mb_substr($exception->getMessage(), 0, 255)]);
            if ($shipment->booking_attempts >= $maxAttempts && $shipment->status === ShipmentStatus::PendingBooking) {
                DB::transaction(fn () => $this->apply(Shipment::query()->whereKey($shipmentId)->lockForUpdate()->firstOrFail(), ShipmentStatus::BookingFailed, "booking_failed:{$shipment->booking_attempts}", 'system', $exception->getMessage(), [], strict: false));

                return;
            }

            throw $exception;
        }
    }

    /**
     * Cập nhật trạng thái. strict = thao tác nhân viên (sai thứ tự → lỗi); webhook = bỏ qua bản trùng/cũ.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateStatus(int $shipmentId, ShipmentStatus $to, string $eventId, string $source, ?string $description = null, array $payload = [], bool $strict = false, ?DateTimeImmutable $occurredAt = null): bool
    {
        return DB::transaction(function () use ($shipmentId, $to, $eventId, $source, $description, $payload, $strict, $occurredAt): bool {
            $shipment = Shipment::query()->whereKey($shipmentId)->lockForUpdate()->firstOrFail();

            return $this->apply($shipment, $to, $eventId, $source, $description, $payload, $strict, $occurredAt);
        });
    }

    public function cancel(int $shipmentId, string $reason): void
    {
        $shipment = Shipment::query()->findOrFail($shipmentId);
        if (! $shipment->status->canMoveTo(ShipmentStatus::Cancelled)) {
            throw FulfillmentRejected::invalidTransition($shipment->status->value, ShipmentStatus::Cancelled->value);
        }

        $carrier = $this->registry->carrier($shipment->carrier_code);
        if ($shipment->tracking_number !== null && $carrier?->capabilities()->cancel) {
            $carrier->cancel($this->data($shipment, $shipment->tracking_number, $shipment->service_code));
        }

        $this->updateStatus($shipmentId, ShipmentStatus::Cancelled, "cancelled:{$shipment->public_id}", 'staff', $reason, [], strict: true);
    }

    /**
     * Đơn bị huỷ → huỷ các shipment chưa rời kho (bỏ qua lỗi phía hãng, ghi log).
     */
    public function cancelOpenShipments(int $orderId, string $reason): void
    {
        foreach (Shipment::query()->where('order_id', $orderId)->get() as $shipment) {
            if (! $shipment->status->canMoveTo(ShipmentStatus::Cancelled)) {
                continue;
            }
            try {
                $this->cancel($shipment->id, "order_cancelled:{$reason}");
            } catch (Throwable $exception) {
                Log::warning('Huỷ vận đơn khi huỷ đơn lỗi.', ['shipment' => $shipment->public_id, 'exception' => $exception]);
                $this->updateStatus($shipment->id, ShipmentStatus::Cancelled, "cancelled:{$shipment->public_id}", 'system', "order_cancelled:{$reason}");
            }
        }
    }

    public function data(Shipment $shipment, ?string $trackingNumber = null, ?string $serviceCode = null): ShipmentData
    {
        $order = $this->orders->find($shipment->order_id);
        $lines = collect($this->orders->lines($shipment->order_id))->keyBy('id');

        return new ShipmentData(
            publicId: $shipment->public_id,
            orderNumber: $order->number ?? '',
            brandId: $shipment->brand_id,
            locationId: $shipment->location_id,
            serviceCode: $serviceCode ?? $shipment->service_code,
            items: $shipment->lines()->get()->map(fn (ShipmentLine $line): array => [
                'sku' => $lines[$line->order_line_id]->sku ?? '', 'name' => $lines[$line->order_line_id]->productName ?? '', 'quantity' => $line->quantity,
            ])->all(),
            recipient: $order->recipient ?? ['full_name' => '', 'phone' => ''],
            address: $order->shippingAddress ?? [],
            codAmount: Money::of($shipment->cod_amount, $shipment->currency_code),
            trackingNumber: $trackingNumber ?? $shipment->tracking_number,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function apply(Shipment $shipment, ShipmentStatus $to, string $eventId, string $source, ?string $description, array $payload, bool $strict, ?DateTimeImmutable $occurredAt = null): bool
    {
        if (! $this->recordEvent($shipment, $to, $eventId, $source, $description, $payload, $occurredAt)) {
            return false; // trùng
        }

        $from = $shipment->status;
        if (! $from->canMoveTo($to)) {
            if ($strict) {
                throw FulfillmentRejected::invalidTransition($from->value, $to->value);
            }

            return false; // webhook cũ/sai thứ tự: đã lưu vết, không lùi trạng thái
        }

        $shipment->update([
            'status' => $to,
            'shipped_at' => $to->hasLeftWarehouse() && $shipment->shipped_at === null ? now() : $shipment->shipped_at,
            'delivered_at' => $to === ShipmentStatus::Delivered ? ($occurredAt ?? now()) : $shipment->delivered_at,
            'lock_version' => $shipment->lock_version + 1,
        ]);

        if ($to === ShipmentStatus::Returned) {
            foreach ($shipment->lines()->get() as $line) {
                $this->returns->restock($shipment->location_id, $line->variant_id, $line->quantity, 'returned_to_sender', "shipment:{$shipment->public_id}:returned");
            }
        }

        $this->syncOrder($shipment->order_id);
        event(new ShipmentStatusChanged($shipment->id, $shipment->order_id, $from->value, $to->value, $shipment->cod_amount, $shipment->brand_id));

        return true;
    }

    private function syncOrder(int $orderId): void
    {
        $statuses = Shipment::query()->where('order_id', $orderId)->pluck('status')->all();
        $this->transitions->setFulfillmentStatus($orderId, FulfillmentProgress::orderStatus($statuses), 'shipments_changed', 'system');

        $order = $this->orders->find($orderId);
        if ($order !== null && FulfillmentProgress::allLeftWarehouse($statuses)) {
            $this->inventory->commit($order->reservationKey); // idempotent: chỉ commit dòng còn active
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordEvent(Shipment $shipment, ShipmentStatus $status, string $eventId, string $source, ?string $description, array $payload, ?DateTimeImmutable $occurredAt = null): bool
    {
        try {
            DB::table('shipment_events')->insert([
                'shipment_id' => $shipment->id, 'status' => $status->value, 'event_id' => $eventId, 'source' => $source,
                'description' => $description === null ? null : mb_substr($description, 0, 255),
                'payload_masked' => $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
                'occurred_at' => $occurredAt ?? now(), 'created_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    /**
     * @param  list<AllocationProposal>  $proposals
     * @param  list<OrderLineData>  $lines
     * @param  list<ReservedLine>  $reserved
     */
    private function assertMatchesReservation(array $proposals, array $lines, array $reserved): void
    {
        $proposedByLine = [];
        $proposedByStock = [];
        $variantByLine = [];
        foreach ($lines as $line) {
            $variantByLine[$line->id] = $line->variantId;
        }
        foreach ($proposals as $proposal) {
            foreach ($proposal->lines as $lineId => $quantity) {
                if (! isset($variantByLine[$lineId]) || $quantity < 1) {
                    throw FulfillmentRejected::allocationMismatch();
                }
                $proposedByLine[$lineId] = ($proposedByLine[$lineId] ?? 0) + $quantity;
                $key = "{$proposal->locationId}:{$variantByLine[$lineId]}";
                $proposedByStock[$key] = ($proposedByStock[$key] ?? 0) + $quantity;
            }
        }

        $reservedByStock = [];
        foreach ($reserved as $row) {
            $key = "{$row->locationId}:{$row->variantId}";
            $reservedByStock[$key] = ($reservedByStock[$key] ?? 0) + $row->quantity;
        }

        foreach ($lines as $line) {
            if (($proposedByLine[$line->id] ?? 0) !== $line->quantity) {
                throw FulfillmentRejected::allocationMismatch();
            }
        }
        foreach ($proposedByStock as $key => $quantity) {
            if ($quantity > ($reservedByStock[$key] ?? 0)) {
                throw FulfillmentRejected::allocationMismatch();
            }
        }
    }
}
