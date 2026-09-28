<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\Data\ReservationRequest;
use Modules\Inventory\Contracts\Data\ReservedLine;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\StockUnavailable;
use Modules\Inventory\Domain\Allocation;
use Modules\Inventory\Domain\InsufficientStock;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Domain\ReservationStatus;
use Modules\Inventory\Domain\StockLevel;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Events\StockCommitted;
use Modules\Inventory\Events\StockReleased;
use Modules\Inventory\Events\StockReserved;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockReservation;

/**
 * Giữ hàng atomic (invariant Core — rule R14): khoá dòng tồn theo thứ tự cố định, kiểm ATS,
 * ghi reservation + sổ biến động trong một transaction.
 *
 * @see docs/08-inventory/inventory.md §4
 */
final class ReservationService implements InventoryReservation
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly ChannelLocations $channelLocations,
        private readonly VariantDirectory $variants,
    ) {}

    public function reserve(ReservationRequest $request): array
    {
        return DB::transaction(function () use ($request): array {
            $quantities = [];
            foreach ($request->lines as $line) {
                $quantities[$line->variantId] = ($quantities[$line->variantId] ?? 0) + $line->quantity;
            }
            ksort($quantities);

            $variants = $this->variants->find(array_keys($quantities));
            $candidates = [];
            $pairs = [];
            foreach ($quantities as $variantId => $quantity) {
                $variant = $variants[$variantId] ?? null;
                if ($variant === null || $variant->status !== 'active' || $quantity <= 0) {
                    throw new StockUnavailable($variantId, $quantity, 0);
                }
                $candidates[$variantId] = $this->channelLocations->forBrand($request->channelId, $variant->brandId);
                foreach ($candidates[$variantId] as $locationId) {
                    $pairs[] = [$locationId, $variantId];
                }
            }

            // Dòng tồn là điểm tuần tự hoá: khoá xong mới kiểm tra idempotency (đọc thường, không khoá —
            // khoá dòng chưa tồn tại sẽ lấy gap lock và gây deadlock khi nhiều đơn cùng chèn reservation).
            $records = $this->ledger->lock($pairs);

            $existing = StockReservation::query()->where('reservation_key', $request->key)->where('status', ReservationStatus::Active)->get();
            if ($existing->isNotEmpty()) {
                return $existing->map(fn (StockReservation $row): ReservedLine => new ReservedLine($row->variant_id, $row->location_id, $row->quantity))->values()->all();
            }

            $expiresAt = $request->ttlSeconds === null ? null : now()->addSeconds($request->ttlSeconds);
            $reserved = [];

            foreach ($quantities as $variantId => $quantity) {
                $levels = array_values(array_filter(array_map(
                    fn (int $locationId): ?StockLevel => ($records["{$locationId}:{$variantId}"] ?? null)?->toDomain(),
                    $candidates[$variantId],
                )));

                try {
                    $plan = Allocation::allocate($variantId, $quantity, $levels);
                } catch (InsufficientStock $exception) {
                    throw new StockUnavailable($exception->variantId, $exception->requested, $exception->available);
                }

                foreach ($plan as $locationId => $take) {
                    $record = $records["{$locationId}:{$variantId}"];
                    $this->ledger->save($record, $record->toDomain()->reserve($take), MovementType::Reserve, null, $request->key);
                    StockReservation::query()->create([
                        'reservation_key' => $request->key,
                        'location_id' => $locationId,
                        'variant_id' => $variantId,
                        'quantity' => $take,
                        'status' => ReservationStatus::Active,
                        'expires_at' => $expiresAt,
                    ]);
                    $reserved[] = new ReservedLine($variantId, $locationId, $take);
                }
            }

            event(new StockReserved($request->key, array_keys($quantities)));
            event(new AvailabilityChanged(array_keys($quantities)));

            return $reserved;
        }, attempts: 3);
    }

    public function release(string $key, string $reason): void
    {
        $this->finish($key, ReservationStatus::Released, $reason);
    }

    public function commit(string $key): void
    {
        $this->finish($key, ReservationStatus::Committed, null);
    }

    private function finish(string $key, ReservationStatus $to, ?string $reason): void
    {
        DB::transaction(function () use ($key, $to, $reason): void {
            $rows = StockReservation::query()->where('reservation_key', $key)->where('status', ReservationStatus::Active)
                ->orderBy('location_id')->orderBy('variant_id')->lockForUpdate()->get();
            if ($rows->isEmpty()) {
                return;
            }

            $records = $this->ledger->lock($rows->map(fn (StockReservation $row): array => [$row->location_id, $row->variant_id])->all());
            $managesOnHand = Location::query()->whereIn('id', $rows->pluck('location_id'))->get()->mapWithKeys(fn (Location $location): array => [$location->id => $location->managesOnHand()]);

            foreach ($rows as $row) {
                $record = $records["{$row->location_id}:{$row->variant_id}"];
                $level = $record->toDomain();
                $after = $to === ReservationStatus::Committed
                    ? $level->commit($row->quantity, (bool) $managesOnHand[$row->location_id])
                    : $level->release($row->quantity);

                $this->ledger->save($record, $after, $to === ReservationStatus::Committed ? MovementType::Commit : MovementType::Release, $reason, $key);
                $row->update(['status' => $to, 'release_reason' => $reason]);
            }

            $variantIds = $rows->pluck('variant_id')->unique()->values()->all();
            event($to === ReservationStatus::Committed ? new StockCommitted($key, $variantIds) : new StockReleased($key, $variantIds));
            event(new AvailabilityChanged($variantIds));
        }, attempts: 3);
    }
}
