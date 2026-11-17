<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockReceipt;
use Modules\Shared\Application\NumberSequences;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\StoreClock;

/**
 * Phiếu nhập nhanh (stabilization A6) — thao tác SME: chọn/quét SKU + số lượng → "Nhập kho" cộng tồn **một lần** (khoá phiếu,
 * biến động `receive` mang số phiếu), trừ vào hàng đang về nếu có. Phiếu đã nhập không sửa được (sai thì điều chỉnh tồn hoặc
 * kiểm kê). Đơn mua nhiều bước, nhận một phần, giá vốn để plugin (backlog L41).
 */
final class StockReceiptService
{
    public const MAX_LINES = 500;

    public function __construct(
        private readonly StockLedger $ledger,
        private readonly VariantDirectory $variants,
        private readonly IncomingStock $incoming,
        private readonly NumberSequences $sequences,
        private readonly CurrentContext $context,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Tạo hoặc sửa phiếu nháp.
     *
     * @param  array<int, int>  $lines  variant id => số lượng (> 0)
     * @param  int|null  $expectedLockVersion  sửa nháp: phiên bản người dùng đã mở (người khác vừa sửa → `lock_version`)
     */
    public function saveDraft(?StockReceipt $receipt, Location $location, ?string $supplier, ?string $note, array $lines, ?int $expectedLockVersion = null): StockReceipt
    {
        if (! $location->managesOnHand()) {
            throw ValidationException::withMessages(['location_id' => __('inventory::messages.external_authority', ['authority' => $location->stock_authority])]);
        }
        $lines = array_filter($lines, fn (int $quantity): bool => $quantity > 0);
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => __('inventory::messages.receipt_empty')]);
        }
        if (count($lines) > self::MAX_LINES) {
            throw ValidationException::withMessages(['lines' => __('inventory::messages.receipt_too_many', ['max' => self::MAX_LINES])]);
        }
        $unknown = array_diff(array_keys($lines), array_keys($this->variants->find(array_keys($lines))));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['lines' => __('inventory::messages.receipt_unknown_variant')]);
        }

        return DB::transaction(function () use ($receipt, $location, $supplier, $note, $lines, $expectedLockVersion): StockReceipt {
            if ($receipt === null) {
                $actor = $this->context->has() ? $this->context->actor() : null;
                $period = now(StoreClock::timezone())->format('ym');
                $receipt = StockReceipt::query()->create([
                    'number' => sprintf('PN%s-%04d', $period, $this->sequences->next('stock_receipt', $period)),
                    'location_id' => $location->id, 'status' => StockReceipt::DRAFT,
                    'created_by_type' => $actor?->type->value, 'created_by_id' => $actor?->id,
                ]);
            } else {
                $receipt = StockReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
                $this->guardDraft($receipt);
                if ($expectedLockVersion !== null && $receipt->lock_version !== $expectedLockVersion) {
                    throw ValidationException::withMessages(['lock_version' => __('inventory::messages.receipt_stale')]);
                }
            }
            $receipt->fill(['location_id' => $location->id, 'supplier' => $this->text($supplier), 'note' => $this->text($note)]);
            $receipt->lock_version++;
            $receipt->save();
            $receipt->lines()->delete();
            foreach ($lines as $variantId => $quantity) {
                $receipt->lines()->create(['variant_id' => $variantId, 'quantity' => $quantity]);
            }
            $this->audit->record('inventory.receipt.saved', 'stock_receipt', $receipt->id, ['number' => $receipt->number, 'lines' => count($lines)]);

            return $receipt;
        });
    }

    /**
     * Nhập kho: cộng tồn mọi dòng trong một transaction (khoá theo thứ tự cố định của ledger). Gọi lại phiếu đã nhập → lỗi,
     * không cộng lần hai.
     */
    public function receive(StockReceipt $receipt): void
    {
        $received = DB::transaction(function () use ($receipt): array {
            $receipt = StockReceipt::query()->with(['lines', 'location'])->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->guardDraft($receipt);
            $location = $receipt->location;
            if (! $location->managesOnHand()) {
                throw ValidationException::withMessages(['location_id' => __('inventory::messages.external_authority', ['authority' => $location->stock_authority])]);
            }

            $quantities = $receipt->lines->mapWithKeys(fn ($line): array => [$line->variant_id => $line->quantity])->all();
            $records = $this->ledger->lock(array_map(fn (int $variantId): array => [$location->id, $variantId], array_keys($quantities)), createMissing: true);
            $reason = __('inventory::messages.receipt_reason', ['number' => $receipt->number]);
            foreach ($quantities as $variantId => $quantity) {
                $record = $records["{$location->id}:{$variantId}"];
                $this->ledger->save($record, $record->toDomain()->adjust($quantity), MovementType::Receive, $reason, $receipt->number);
                event(new StockAdjusted($location->id, $variantId, $quantity, $reason));
            }
            $receipt->forceFill(['status' => StockReceipt::RECEIVED, 'received_at' => now(), 'lock_version' => $receipt->lock_version + 1])->save();
            $this->audit->record('inventory.receipt.received', 'stock_receipt', $receipt->id, ['number' => $receipt->number, 'location' => $location->code, 'units' => array_sum($quantities)]);
            event(new AvailabilityChanged(array_keys($quantities)));

            return $quantities;
        }, attempts: 3);

        // Hàng đã về: trừ khỏi "hàng đang về" (ADR-047) — phần còn lại vẫn đang về.
        foreach ($this->incoming->forVariants(array_keys($received)) as $variantId => $incoming) {
            $this->incoming->set($variantId, max(0, $incoming->quantity - $received[$variantId]), $incoming->expectedOn, $incoming->source);
        }
    }

    public function cancel(StockReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt): void {
            $receipt = StockReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->guardDraft($receipt);
            $receipt->forceFill(['status' => StockReceipt::CANCELLED, 'lock_version' => $receipt->lock_version + 1])->save();
            $this->audit->record('inventory.receipt.cancelled', 'stock_receipt', $receipt->id, ['number' => $receipt->number]);
        });
    }

    private function guardDraft(StockReceipt $receipt): void
    {
        if ($receipt->status !== StockReceipt::DRAFT) {
            throw ValidationException::withMessages(['receipt' => __('inventory::messages.receipt_not_draft', ['number' => $receipt->number])]);
        }
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
