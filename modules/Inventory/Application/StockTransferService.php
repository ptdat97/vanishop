<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Domain\TransferStatus;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Events\StockTransferCancelled;
use Modules\Inventory\Events\StockTransferCreated;
use Modules\Inventory\Events\StockTransferReceived;
use Modules\Inventory\Events\StockTransferShipped;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockTransfer;
use Modules\Inventory\Persistence\Models\StockTransferLine;

/**
 * Chuyển kho có vòng đời `pending → shipped → received`, `cancelled` (roadmap Phase 1).
 *
 * Invariant:
 * - `pending` không đổi tồn (hàng còn ở kho đi). `shipped` ghi `transfer_out` (trừ `on_hand` kho đi) —
 *   từ đây hàng đang đi đường nên không bán được. `received` ghi `transfer_in` (cộng kho đến).
 * - Huỷ sau `shipped` nhập lại kho đi (movement `transfer_in`, reference `:revert`); huỷ trước `shipped` không đổi tồn.
 * - Thuận chuyển chỉ giữa hai location do VaniShop quản lý tồn vật lý (`managesOnHand`); location do ERP quản lý
 *   thì chuyển diễn ra trên ERP, VaniShop chỉ nhận số mới.
 * - Mọi thay đổi tồn là một movement trong sổ append-only (R30); thao tác nhân viên có audit (R31).
 */
final class StockTransferService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Tạo phiếu chuyển ở trạng thái `pending`.
     *
     * @param  array<int, int>  $quantities  variant_id => số lượng (> 0)
     */
    public function create(Location $from, Location $to, array $quantities, ?string $note = null, ?string $reference = null): StockTransfer
    {
        $quantities = $this->normalize($quantities);
        $this->assertTransferable($from, $to, $quantities);

        return DB::transaction(function () use ($from, $to, $quantities, $note, $reference): StockTransfer {
            $transfer = StockTransfer::query()->create([
                'public_id' => (string) Str::ulid(),
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'status' => TransferStatus::Pending,
                'note' => $note,
                'reference' => $reference,
            ]);

            foreach ($quantities as $variantId => $quantity) {
                StockTransferLine::query()->create(['stock_transfer_id' => $transfer->id, 'variant_id' => $variantId, 'quantity' => $quantity]);
            }

            $variantIds = array_keys($quantities);
            $this->audit->record('inventory.transfer.created', 'stock_transfer', $transfer->id, ['from' => $from->code, 'to' => $to->code, 'lines' => count($variantIds)]);
            event(new StockTransferCreated($transfer->public_id, $from->id, $to->id, $variantIds));

            return $transfer;
        });
    }

    /**
     * Gửi hàng: `pending → shipped`, trừ `on_hand` kho đi.
     */
    public function ship(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            $locked = $this->lock($transfer);
            $this->assertCanMove($locked, TransferStatus::Shipped);

            $lines = $locked->lines()->get();
            $from = Location::query()->findOrFail($locked->from_location_id);
            $records = $this->ledger->lock($this->pairs($from->id, $lines), createMissing: true);

            foreach ($lines as $line) {
                $record = $records["{$from->id}:{$line->variant_id}"];

                try {
                    $after = $record->toDomain()->transferOut($line->quantity);
                } catch (InvalidArgumentException $exception) {
                    throw ValidationException::withMessages(['business' => $exception->getMessage()]);
                }

                $this->ledger->save($record, $after, MovementType::TransferOut, "transfer {$locked->public_id}", "transfer:{$locked->public_id}:out");
            }

            $locked->update(['status' => TransferStatus::Shipped, 'shipped_at' => now(), 'lock_version' => $locked->lock_version + 1]);

            $variantIds = $this->variantIds($lines);
            $this->audit->record('inventory.transfer.shipped', 'stock_transfer', $locked->id, ['from' => $from->code, 'lines' => count($variantIds)]);
            event(new StockTransferShipped($locked->public_id, $locked->from_location_id, $locked->to_location_id, $variantIds));
            event(new AvailabilityChanged($variantIds));
        }, attempts: 3);
    }

    /**
     * Nhận hàng: `shipped → received`, cộng `on_hand` kho đến. Nhận thiếu được (chênh lệch coi là hao hụt trên đường).
     *
     * @param  array<int, int>  $received  variant_id => số nhận thực tế (mặc định nhận đủ)
     */
    public function receive(StockTransfer $transfer, array $received = []): void
    {
        DB::transaction(function () use ($transfer, $received): void {
            $locked = $this->lock($transfer);
            $this->assertCanMove($locked, TransferStatus::Received);

            $lines = $locked->lines()->get();
            $to = Location::query()->findOrFail($locked->to_location_id);
            $records = $this->ledger->lock($this->pairs($to->id, $lines), createMissing: true);

            foreach ($lines as $line) {
                $quantity = $received[$line->variant_id] ?? $line->quantity;
                if ($quantity < 0 || $quantity > $line->quantity) {
                    throw ValidationException::withMessages(['lines' => __('inventory::messages.transfer_received_range', ['variant' => $line->variant_id, 'max' => $line->quantity])]);
                }

                if ($quantity > 0) {
                    $record = $records["{$to->id}:{$line->variant_id}"];
                    $this->ledger->save($record, $record->toDomain()->transferIn($quantity), MovementType::TransferIn, "transfer {$locked->public_id}", "transfer:{$locked->public_id}:in");
                }
                $line->update(['received_quantity' => $quantity]);
            }

            $locked->update(['status' => TransferStatus::Received, 'received_at' => now(), 'lock_version' => $locked->lock_version + 1]);

            $variantIds = $this->variantIds($lines);
            $this->audit->record('inventory.transfer.received', 'stock_transfer', $locked->id, ['to' => $to->code, 'lines' => count($variantIds)]);
            event(new StockTransferReceived($locked->public_id, $locked->from_location_id, $locked->to_location_id, $variantIds));
            event(new AvailabilityChanged($variantIds));
        }, attempts: 3);
    }

    /**
     * Huỷ phiếu. `pending` → không đổi tồn. `shipped` → nhập lại kho đi.
     */
    public function cancel(StockTransfer $transfer, string $reason): void
    {
        DB::transaction(function () use ($transfer, $reason): void {
            $locked = $this->lock($transfer);
            $this->assertCanMove($locked, TransferStatus::Cancelled);

            $lines = $locked->lines()->get();
            $variantIds = $this->variantIds($lines);
            $restocked = $locked->status->isInTransit();

            if ($restocked) {
                $from = Location::query()->findOrFail($locked->from_location_id);
                $records = $this->ledger->lock($this->pairs($from->id, $lines), createMissing: true);
                foreach ($lines as $line) {
                    $record = $records["{$from->id}:{$line->variant_id}"];
                    $this->ledger->save($record, $record->toDomain()->transferIn($line->quantity), MovementType::TransferIn, "transfer {$locked->public_id} cancelled", "transfer:{$locked->public_id}:revert");
                }
            }

            $locked->update([
                'status' => TransferStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => mb_substr($reason, 0, 255),
                'lock_version' => $locked->lock_version + 1,
            ]);

            $this->audit->record('inventory.transfer.cancelled', 'stock_transfer', $locked->id, ['reason' => $reason, 'restocked' => $restocked]);
            event(new StockTransferCancelled($locked->public_id, $locked->from_location_id, $locked->to_location_id, $variantIds, $restocked));
            if ($restocked) {
                event(new AvailabilityChanged($variantIds));
            }
        }, attempts: 3);
    }

    private function lock(StockTransfer $transfer): StockTransfer
    {
        return StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
    }

    private function assertCanMove(StockTransfer $transfer, TransferStatus $to): void
    {
        if (! $transfer->status->canMoveTo($to)) {
            throw ValidationException::withMessages(['business' => __('inventory::messages.transfer_invalid_transition', ['from' => $transfer->status->value, 'to' => $to->value])]);
        }
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function assertTransferable(Location $from, Location $to, array $quantities): void
    {
        if ($quantities === []) {
            throw ValidationException::withMessages(['lines' => __('inventory::messages.transfer_no_lines')]);
        }
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to_location_id' => __('inventory::messages.transfer_same_location')]);
        }
        if (! $from->managesOnHand() || ! $to->managesOnHand()) {
            throw ValidationException::withMessages(['business' => __('inventory::messages.transfer_external_authority')]);
        }
    }

    /**
     * @param  array<int, int>  $quantities
     * @return array<int, int>
     */
    private function normalize(array $quantities): array
    {
        $normalized = [];
        foreach ($quantities as $variantId => $quantity) {
            if ($quantity > 0) {
                $normalized[(int) $variantId] = ($normalized[(int) $variantId] ?? 0) + (int) $quantity;
            }
        }
        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  Collection<int, StockTransferLine>  $lines
     * @return list<array{0: int, 1: int}>
     */
    private function pairs(int $locationId, $lines): array
    {
        return $lines->map(fn (StockTransferLine $line): array => [$locationId, $line->variant_id])->values()->all();
    }

    /**
     * @param  Collection<int, StockTransferLine>  $lines
     * @return list<int>
     */
    private function variantIds($lines): array
    {
        return $lines->pluck('variant_id')->map(fn ($id): int => (int) $id)->values()->all();
    }
}
