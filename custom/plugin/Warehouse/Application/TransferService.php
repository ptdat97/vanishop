<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Application;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Contracts\Data\LocationData;
use Modules\Inventory\Contracts\LocationRegistry;
use Modules\Inventory\Contracts\StockMovementRejected;
use Modules\Inventory\Contracts\StockMovements;
use Modules\Inventory\Events\StockTransferCancelled;
use Modules\Inventory\Events\StockTransferCreated;
use Modules\Inventory\Events\StockTransferReceived;
use Modules\Inventory\Events\StockTransferShipped;
use Plugin\Warehouse\Domain\TransferStatus;
use Plugin\Warehouse\Persistence\Transfer;
use Plugin\Warehouse\Persistence\TransferLine;

/**
 * Chuyển kho có vòng đời `pending → shipped → received`, `cancelled` (ADR-042: chứng từ ở plugin, tồn ở Core).
 *
 * Invariant:
 * - `pending` không đổi tồn (hàng còn ở kho đi). `shipped` xuất kho đi (`StockMovements::transferOut`) — từ đây hàng
 *   đang đi đường nên không bán được. `received` nhập kho đến. Huỷ sau `shipped` nhập lại kho đi (reference `:revert`).
 * - Chỉ giữa hai kho do VaniShop quản lý tồn vật lý; kho do ERP quản lý thì chuyển trên ERP, VaniShop chỉ nhận số mới.
 * - Trạng thái phiếu và biến động tồn trong cùng một transaction; thao tác nhân viên có audit.
 */
final class TransferService
{
    public function __construct(
        private readonly StockMovements $stock,
        private readonly LocationRegistry $locations,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Tạo phiếu chuyển ở trạng thái `pending`.
     *
     * @param  array<int, int>  $quantities  variant_id => số lượng (> 0)
     */
    public function create(LocationData $from, LocationData $to, array $quantities, ?string $note = null, ?string $reference = null): Transfer
    {
        $quantities = $this->normalize($quantities);
        $this->assertTransferable($from, $to, $quantities);

        return DB::transaction(function () use ($from, $to, $quantities, $note, $reference): Transfer {
            $transfer = Transfer::query()->create([
                'public_id' => (string) Str::ulid(), 'from_location_id' => $from->id, 'to_location_id' => $to->id,
                'status' => TransferStatus::Pending, 'note' => $note, 'reference' => $reference,
            ]);
            foreach ($quantities as $variantId => $quantity) {
                TransferLine::query()->create(['stock_transfer_id' => $transfer->id, 'variant_id' => $variantId, 'quantity' => $quantity]);
            }

            $this->audit->record('inventory.transfer.created', 'stock_transfer', $transfer->id, ['from' => $from->code, 'to' => $to->code, 'lines' => count($quantities)]);
            event(new StockTransferCreated($transfer->public_id, $from->id, $to->id, array_keys($quantities)));

            return $transfer;
        });
    }

    /**
     * Gửi hàng: `pending → shipped`, xuất kho đi.
     */
    public function ship(Transfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            $locked = $this->lock($transfer);
            $this->assertCanMove($locked, TransferStatus::Shipped);
            $lines = $locked->lines()->get();

            $this->move(fn () => $this->stock->transferOut($locked->from_location_id, $this->quantities($lines), "transfer {$locked->public_id}", "transfer:{$locked->public_id}:out"));
            $locked->update(['status' => TransferStatus::Shipped, 'shipped_at' => now(), 'lock_version' => $locked->lock_version + 1]);

            $variantIds = $this->variantIds($lines);
            $this->audit->record('inventory.transfer.shipped', 'stock_transfer', $locked->id, ['from' => $this->code($locked->from_location_id), 'lines' => count($variantIds)]);
            event(new StockTransferShipped($locked->public_id, $locked->from_location_id, $locked->to_location_id, $variantIds));
        }, attempts: 3);
    }

    /**
     * Nhận hàng: `shipped → received`, nhập kho đến. Nhận thiếu được (chênh lệch coi là hao hụt trên đường).
     *
     * @param  array<int, int>  $received  variant_id => số nhận thực tế (mặc định nhận đủ)
     */
    public function receive(Transfer $transfer, array $received = []): void
    {
        DB::transaction(function () use ($transfer, $received): void {
            $locked = $this->lock($transfer);
            $this->assertCanMove($locked, TransferStatus::Received);
            $lines = $locked->lines()->get();

            $quantities = [];
            foreach ($lines as $line) {
                $quantity = $received[$line->variant_id] ?? $line->quantity;
                if ($quantity < 0 || $quantity > $line->quantity) {
                    throw ValidationException::withMessages(['lines' => __('vani-warehouse::messages.transfer_received_range', ['variant' => $line->variant_id, 'max' => $line->quantity])]);
                }
                $quantities[$line->variant_id] = $quantity;
                $line->update(['received_quantity' => $quantity]);
            }
            $this->move(fn () => $this->stock->transferIn($locked->to_location_id, $quantities, "transfer {$locked->public_id}", "transfer:{$locked->public_id}:in"));
            $locked->update(['status' => TransferStatus::Received, 'received_at' => now(), 'lock_version' => $locked->lock_version + 1]);

            $variantIds = $this->variantIds($lines);
            $this->audit->record('inventory.transfer.received', 'stock_transfer', $locked->id, ['to' => $this->code($locked->to_location_id), 'lines' => count($variantIds)]);
            event(new StockTransferReceived($locked->public_id, $locked->from_location_id, $locked->to_location_id, $variantIds));
        }, attempts: 3);
    }

    /**
     * Huỷ phiếu. `pending` → không đổi tồn. `shipped` → nhập lại kho đi.
     */
    public function cancel(Transfer $transfer, string $reason): void
    {
        DB::transaction(function () use ($transfer, $reason): void {
            $locked = $this->lock($transfer);
            $this->assertCanMove($locked, TransferStatus::Cancelled);
            $lines = $locked->lines()->get();
            $restocked = $locked->status->isInTransit();

            if ($restocked) {
                $this->move(fn () => $this->stock->transferIn($locked->from_location_id, $this->quantities($lines), "transfer {$locked->public_id} cancelled", "transfer:{$locked->public_id}:revert"));
            }
            $locked->update(['status' => TransferStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => mb_substr($reason, 0, 255), 'lock_version' => $locked->lock_version + 1]);

            $this->audit->record('inventory.transfer.cancelled', 'stock_transfer', $locked->id, ['reason' => $reason, 'restocked' => $restocked]);
            event(new StockTransferCancelled($locked->public_id, $locked->from_location_id, $locked->to_location_id, $this->variantIds($lines), $restocked));
        }, attempts: 3);
    }

    private function lock(Transfer $transfer): Transfer
    {
        return Transfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  callable(): void  $call
     */
    private function move(callable $call): void
    {
        try {
            $call();
        } catch (StockMovementRejected $exception) {
            throw ValidationException::withMessages(['business' => $exception->getMessage()]);
        }
    }

    private function assertCanMove(Transfer $transfer, TransferStatus $to): void
    {
        if (! $transfer->status->canMoveTo($to)) {
            throw ValidationException::withMessages(['business' => __('vani-warehouse::messages.transfer_invalid_transition', ['from' => $transfer->status->value, 'to' => $to->value])]);
        }
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function assertTransferable(LocationData $from, LocationData $to, array $quantities): void
    {
        if ($quantities === []) {
            throw ValidationException::withMessages(['lines' => __('vani-warehouse::messages.transfer_no_lines')]);
        }
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to_location_id' => __('vani-warehouse::messages.transfer_same_location')]);
        }
        if (! $from->managesOnHand() || ! $to->managesOnHand()) {
            throw ValidationException::withMessages(['business' => __('vani-warehouse::messages.transfer_external_authority')]);
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

    private function code(int $locationId): string
    {
        return $this->locations->find($locationId)?->code ?? (string) $locationId;
    }

    /**
     * @param  Collection<int, TransferLine>  $lines
     * @return array<int, int>
     */
    private function quantities(Collection $lines): array
    {
        return $lines->mapWithKeys(fn (TransferLine $line): array => [$line->variant_id => $line->quantity])->all();
    }

    /**
     * @param  Collection<int, TransferLine>  $lines
     * @return list<int>
     */
    private function variantIds(Collection $lines): array
    {
        return $lines->pluck('variant_id')->map(fn ($id): int => (int) $id)->values()->all();
    }
}
