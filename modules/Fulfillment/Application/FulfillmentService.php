<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Extension\Facades\Hook;
use Modules\Fulfillment\Contracts\Data\AllocationProposal;
use Modules\Fulfillment\Contracts\Data\ShipmentData;
use Modules\Fulfillment\Contracts\Data\SourcingRequest;
use Modules\Fulfillment\Contracts\FulfillmentRejected;
use Modules\Fulfillment\Contracts\ShipmentBooking;
use Modules\Fulfillment\Domain\AllocationCheck;
use Modules\Fulfillment\Domain\BookingRetry;
use Modules\Fulfillment\Domain\FulfillmentProgress;
use Modules\Fulfillment\Domain\ShipmentCod;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Events\ShipmentCreated;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Persistence\Models\ShipmentLine;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Contracts\Data\ReservedLine;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\ExternalCalls;
use Throwable;

/**
 * docs/09-order/fulfillment.md §3 (chế độ internal). Mọi thay đổi shipment: khoá dòng shipment → ghi shipment_events
 * (unique chống trùng) → không cho lùi trạng thái → tính lại fulfillment_status của đơn → commit giữ hàng khi mọi
 * shipment đã rời kho → nhập lại kho khi hàng hoàn về.
 */
final class FulfillmentService implements ShipmentBooking
{
    /** Thời hạn giữ chỗ khi đặt vận đơn — dài hơn timeout HTTP của plugin hãng (thường 30 giây). */
    private const BOOKING_LEASE_SECONDS = 120;

    public function __construct(
        private readonly CarrierRegistry $registry,
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly InventoryReservation $inventory,
        private readonly InventoryReturns $returns,
        private readonly AuditLogger $audit,
        private readonly CarrierSelection $carriers,
    ) {}

    /**
     * Tạo shipment cho đơn đã xác nhận theo SourcingStrategy. Trả các shipment id vừa tạo.
     *
     * Carrier: chỉ định tường minh (nhân viên) → quy tắc `CarrierAssignment` của plugin (EP v3 X3) → carrier của phương thức giao khách chọn (`shippingMethod.source`,
     * dịch vụ = `shippingMethod.code`) nếu carrier đó đang bật → carrier mặc định (`vanishop.fulfillment.default_carrier`).
     *
     * @return list<int>
     */
    public function createForOrder(int $orderId, ?string $carrierCode = null): array
    {
        [$carrierCode, $serviceCode, $assignedBy] = $this->carriers->choose($orderId, $carrierCode);
        $carrier = $this->registry->carrier($carrierCode) ?? throw new \InvalidArgumentException("Carrier [{$carrierCode}] chưa đăng ký.");

        $ids = DB::transaction(function () use ($orderId, $carrierCode, $serviceCode, $assignedBy): array {
            $order = $this->orders->find($orderId) ?? throw FulfillmentRejected::orderNotReady('missing');
            if (! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing], true)) {
                throw FulfillmentRejected::orderNotReady($order->status->value);
            }
            if (Shipment::query()->where('order_id', $orderId)->where('status', '!=', ShipmentStatus::Cancelled)->lockForUpdate()->exists()) {
                throw FulfillmentRejected::alreadyHasShipments();
            }

            $lines = $this->orders->lines($orderId);
            $reserved = $this->inventory->reservedLines($order->reservationKey);
            $strategy = $this->registry->sourcing()
                ?? throw new \InvalidArgumentException('SourcingStrategy chưa đăng ký.');
            $proposals = $strategy->allocate(new SourcingRequest($orderId, $lines, $reserved, $order->shippingAddress));
            $orderLines = [];
            foreach ($lines as $line) {
                $orderLines[$line->id] = ['variant' => $line->variantId, 'quantity' => $line->quantity];
            }
            if (! AllocationCheck::matches(
                array_map(fn (AllocationProposal $proposal): array => ['location' => $proposal->locationId, 'lines' => $proposal->lines], $proposals),
                $orderLines,
                array_map(fn (ReservedLine $row): array => ['location' => $row->locationId, 'variant' => $row->variantId, 'quantity' => $row->quantity], $reserved),
            )) {
                throw FulfillmentRejected::allocationMismatch();
            }

            $ids = [];
            // Thu hộ: cổng thu tiền khi giao đặt đơn ở `cod_pending` (GatewayCapabilities::collectsOnDelivery).
            $cod = ShipmentCod::amounts(count($proposals), $order->paymentStatus === 'cod_pending', $order->totalAmount);
            foreach ($proposals as $index => $proposal) {
                $shipment = Shipment::query()->create([
                    'public_id' => (string) Str::ulid(), 'order_id' => $orderId, 'location_id' => $proposal->locationId,
                    'carrier_code' => $carrierCode, 'service_code' => $serviceCode, 'cod_amount' => $cod[$index], 'currency_code' => $order->currencyCode,
                    'status' => ShipmentStatus::PendingBooking,
                ]);
                foreach ($proposal->lines as $lineId => $quantity) {
                    ShipmentLine::query()->create(['shipment_id' => $shipment->id, 'order_line_id' => $lineId, 'variant_id' => $orderLines[$lineId]['variant'], 'quantity' => $quantity]);
                }
                $this->recordEvent($shipment, ShipmentStatus::PendingBooking, "created:{$shipment->public_id}", 'system', $assignedBy, []);
                event(new ShipmentCreated($shipment->id, $orderId, $carrierCode));
                $ids[] = $shipment->id;
            }

            if ($order->status === OrderStatus::Confirmed) {
                $this->transitions->transition($orderId, OrderStatus::Processing, 'shipments_created', 'system');
            }
            $this->syncOrder($orderId);

            return $ids;
        });

        if ($carrier->capabilities()->autoBooking && config('vanishop.fulfillment.booking', 'manual') === 'on_create') {
            $this->pushToCarrier($ids);
        }

        return $ids;
    }

    /**
     * "Giao trước phần có hàng" (0.5.36, backlog L37 — Owner 2026-11-19): tách vận đơn đang chờ đặt thành hai — `$move`
     * (order_line_id → số lượng) sang vận đơn mới cùng kho/hãng, ở "Chờ đóng gói" (vd. món đặt trước còn chờ hàng); vận đơn
     * cũ giữ phần còn lại để đẩy lên hãng trước. COD thu theo giá trị từng kiện: vận đơn mới thu tiền hàng của phần chuyển
     * (thành tiền dòng đã phân bổ giảm giá, chia theo số lượng), vận đơn cũ giữ phần còn lại + phí ship. Phí ship kiện thứ
     * hai cửa hàng chịu. Trừ tồn vẫn khi mọi vận đơn đã rời kho.
     *
     * @param  array<int, int>  $move
     */
    public function split(int $shipmentId, array $move, ?int $staffId = null): int
    {
        $move = array_filter(array_map('intval', $move), fn (int $quantity): bool => $quantity > 0);

        return DB::transaction(function () use ($shipmentId, $move, $staffId): int {
            $shipment = Shipment::query()->whereKey($shipmentId)->lockForUpdate()->firstOrFail();
            if (! $shipment->status->awaitsBooking() || $shipment->booking_lease !== null) {
                throw FulfillmentRejected::invalidTransition($shipment->status->value, 'split');
            }
            $lines = ShipmentLine::query()->where('shipment_id', $shipment->id)->get()->keyBy('order_line_id');
            $remaining = 0;
            foreach ($lines as $lineId => $line) {
                $moved = $move[$lineId] ?? 0;
                if ($moved > $line->quantity) {
                    throw FulfillmentRejected::splitInvalid();
                }
                $remaining += $line->quantity - $moved;
            }
            if ($move === [] || array_diff_key($move, $lines->all()) !== [] || $remaining === 0) {
                throw FulfillmentRejected::splitInvalid();
            }

            $orderLines = collect($this->orders->lines($shipment->order_id))->keyBy('id');
            $movedCod = 0;
            if ($shipment->cod_amount > 0) {
                foreach ($move as $lineId => $quantity) {
                    $orderLine = $orderLines[$lineId] ?? null;
                    $movedCod += $orderLine === null || $orderLine->quantity === 0 ? 0 : intdiv($orderLine->totalAmount * $quantity, $orderLine->quantity);
                }
                $movedCod = min($movedCod, $shipment->cod_amount);
            }

            $new = Shipment::query()->create([
                'public_id' => (string) Str::ulid(), 'order_id' => $shipment->order_id, 'location_id' => $shipment->location_id,
                'carrier_code' => $shipment->carrier_code, 'service_code' => $shipment->service_code, 'cod_amount' => $movedCod,
                'currency_code' => $shipment->currency_code, 'status' => ShipmentStatus::PendingBooking,
            ]);
            foreach ($move as $lineId => $quantity) {
                $line = $lines[$lineId];
                ShipmentLine::query()->create(['shipment_id' => $new->id, 'order_line_id' => $lineId, 'variant_id' => $line->variant_id, 'quantity' => $quantity]);
                $quantity === $line->quantity ? $line->delete() : $line->update(['quantity' => $line->quantity - $quantity]);
            }
            $shipment->update(['cod_amount' => $shipment->cod_amount - $movedCod]);

            $this->recordEvent($new, ShipmentStatus::PendingBooking, "split:{$shipment->public_id}:{$new->public_id}", 'staff', "Tách từ vận đơn {$shipment->public_id}", []);
            $this->audit->record('fulfillment.shipment.split', 'shipment', $shipment->id, ['new_shipment' => $new->public_id, 'lines' => $move, 'cod_moved' => $movedCod, 'staff' => $staffId]);
            event(new ShipmentCreated($new->id, $new->order_id, $new->carrier_code));
            $this->syncOrder($shipment->order_id);

            return $new->id;
        });
    }

    /**
     * "Đẩy lên hãng" (ADR-046 chốt ②) — xem ShipmentBooking. Vận đơn đặt lỗi được đặt lại từ đầu (đủ số lần thử).
     *
     * @param  list<int>  $shipmentIds
     */
    public function pushToCarrier(array $shipmentIds): int
    {
        $queued = 0;
        $shipments = Shipment::query()->whereIn('id', $shipmentIds)->get()->filter(fn (Shipment $shipment): bool => $shipment->status->awaitsBooking());
        foreach ($shipments as $shipment) {
            if (! ($this->registry->carrier($shipment->carrier_code)?->capabilities()->autoBooking ?? false) || $this->pushBlockers($shipment) !== []) {
                continue;
            }
            $shipment->update(['booking_attempts' => 0]);
            BookShipmentJob::dispatch($shipment->id)->afterCommit();
            $queued++;
        }

        return $queued;
    }

    /**
     * Lý do chưa được đẩy lên hãng (hook `vani.fulfillment.before_push`, EP v3 X2): vd. thiếu khối lượng, phường chưa chuẩn
     * hoá. Áp cho "Đẩy lên hãng" (nút, hàng loạt, `on_create`, plugin) và nhập mã vận đơn tay.
     *
     * @return list<string>
     */
    public function pushBlockers(Shipment|int $shipment): array
    {
        $shipment = $shipment instanceof Shipment ? $shipment : Shipment::query()->findOrFail($shipment);

        return array_values(array_filter(Hook::collect('vani.fulfillment.before_push', $this->data($shipment)), fn ($message): bool => is_string($message) && $message !== ''));
    }

    /**
     * Ghi nhận mã vận đơn (nhân viên nhập với carrier thủ công, hoặc kết quả đặt tự động).
     */
    public function book(int $shipmentId, ?string $trackingNumber, ?string $serviceCode = null, ?string $labelUrl = null): void
    {
        // Ba bước (backlog L31): giữ chỗ trong transaction ngắn → gọi hãng NGOÀI transaction (không giữ khoá dòng khi hãng
        // chậm) → ghi kết quả nếu lease còn là của lần này. Lần đặt khác trong lúc lease còn hạn bị từ chối (không đặt trùng).
        $lease = (string) Str::ulid();
        $shipment = DB::transaction(function () use ($shipmentId, $lease): Shipment {
            $shipment = Shipment::query()->whereKey($shipmentId)->lockForUpdate()->firstOrFail();
            if (! $shipment->status->awaitsBooking()) {
                throw FulfillmentRejected::invalidTransition($shipment->status->value, ShipmentStatus::Created->value);
            }
            if ($shipment->booking_started_at !== null && $shipment->booking_started_at->getTimestamp() > now()->getTimestamp() - self::BOOKING_LEASE_SECONDS) {
                throw FulfillmentRejected::bookingInProgress();
            }
            $shipment->update(['booking_started_at' => now(), 'booking_lease' => $lease]);

            return $shipment;
        });

        try {
            $carrier = $this->registry->carrier($shipment->carrier_code);
            $booked = null;
            if ($carrier !== null) {
                ExternalCalls::guard("carrier.createShipment:{$shipment->carrier_code}");
                $booked = $carrier->createShipment($this->data($shipment, $trackingNumber, $serviceCode));
            }

            DB::transaction(function () use ($shipmentId, $lease, $booked, $trackingNumber, $serviceCode, $labelUrl): void {
                $shipment = Shipment::query()->whereKey($shipmentId)->lockForUpdate()->firstOrFail();
                if ($shipment->booking_lease !== $lease || ! $shipment->status->awaitsBooking()) {
                    // Vận đơn vừa bị huỷ, hoặc lease hết hạn và lần khác đã đặt: không ghi đè. Đơn có thể đã tạo ở hãng.
                    Log::warning('Kết quả đặt vận đơn đến sau khi vận đơn đã đổi — kiểm tra đơn ở hãng.', [
                        'shipment' => $shipment->public_id, 'carrier' => $shipment->carrier_code, 'tracking' => $booked?->trackingNumber,
                    ]);

                    throw FulfillmentRejected::invalidTransition($shipment->status->value, ShipmentStatus::Created->value);
                }
                try {
                    $shipment->update([
                        'tracking_number' => $booked->trackingNumber ?? $trackingNumber, 'service_code' => $booked->serviceCode ?? $serviceCode,
                        'label_url' => $booked->labelUrl ?? $labelUrl, 'last_error' => null,
                        'booking_started_at' => null, 'booking_lease' => null,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    throw FulfillmentRejected::trackingTaken();
                }

                $this->apply($shipment, ShipmentStatus::Created, "booked:{$shipment->public_id}", 'system', null, [], strict: true);
            });
        } catch (Throwable $exception) {
            // Hãng lỗi, mã vận đơn trùng…: trả lease để lần sau (job thử lại, nhân viên bấm lại) đặt được ngay.
            Shipment::query()->whereKey($shipmentId)->where('booking_lease', $lease)->update(['booking_started_at' => null, 'booking_lease' => null]);

            throw $exception;
        }
    }

    /**
     * Job: đặt vận đơn qua API hãng. Lỗi quá số lần thử → booking_failed (nhân viên đặt lại hoặc chuyển thủ công).
     */
    public function bookAutomatically(int $shipmentId, int $maxAttempts): void
    {
        $shipment = Shipment::query()->find($shipmentId);
        if ($shipment === null || ! $shipment->status->awaitsBooking()) {
            return;
        }

        $shipment->increment('booking_attempts');
        try {
            $this->book($shipmentId, null);
        } catch (Throwable $exception) {
            if ($exception instanceof FulfillmentRejected && $exception->errorCode() === 'shipment.booking_in_progress') {
                // Lần đặt khác (nhân viên bấm, worker khác) đang gọi hãng: để lần đó ghi kết quả, không tính là lỗi.
                $shipment->decrement('booking_attempts');

                return;
            }
            $shipment->refresh();
            if (! $shipment->status->awaitsBooking()) {
                // Lần đẩy khác (bấm trùng, plugin) vừa đặt xong hoặc vận đơn vừa bị huỷ: không còn gì để làm.
                return;
            }
            Log::warning('Đặt vận đơn với hãng lỗi.', ['shipment' => $shipment->public_id, 'carrier' => $shipment->carrier_code, 'exception' => $exception]);
            $shipment->update(['last_error' => mb_substr($exception->getMessage(), 0, 255)]);
            if (BookingRetry::exhausted((int) $shipment->booking_attempts, $maxAttempts, $shipment->status)) {
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
            ExternalCalls::guard("carrier.cancel:{$shipment->carrier_code}");
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

        return vani_filter('vani.service.fulfillment.shipment_data', new ShipmentData(
            publicId: $shipment->public_id,
            orderNumber: $order->number ?? '',
            locationId: $shipment->location_id,
            serviceCode: $serviceCode ?? $shipment->service_code,
            items: $shipment->lines()->get()->map(fn (ShipmentLine $line): array => [
                'sku' => $lines[$line->order_line_id]->sku ?? '', 'name' => $lines[$line->order_line_id]->productName ?? '', 'quantity' => $line->quantity,
            ])->all(),
            recipient: $order->recipient ?? ['full_name' => '', 'phone' => ''],
            address: $order->shippingAddress ?? [],
            codAmount: Money::of($shipment->cod_amount, $shipment->currency_code),
            trackingNumber: $trackingNumber ?? $shipment->tracking_number,
        ), $shipment);
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
        event(new ShipmentStatusChanged($shipment->id, $shipment->order_id, $from->value, $to->value, $shipment->cod_amount, $shipment->public_id, $shipment->tracking_number, $shipment->carrier_code));

        return true;
    }

    private function syncOrder(int $orderId): void
    {
        $statuses = Shipment::query()->where('order_id', $orderId)->pluck('status')->all();
        $this->transitions->setFulfillmentStatus($orderId, FulfillmentProgress::orderStatus($statuses), 'shipments_changed', 'system');
        $this->transitions->setDeliveryIssue($orderId, FulfillmentProgress::deliveryIssue($statuses), 'shipments_changed', 'system');

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
}
