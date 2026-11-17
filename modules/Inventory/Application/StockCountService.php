<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockCount;
use Modules\Inventory\Persistence\Models\StockCountLine;
use Modules\Shared\Application\NumberSequences;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\StoreClock;

/**
 * Kiểm kê nhanh (stabilization A7) — đếm trong lúc cửa hàng vẫn bán: mỗi lần ghi số đếm của SKU lưu kèm tồn hệ thống lúc đó;
 * "Áp dụng" chỉ cộng/trừ phần chênh `số đếm − tồn lúc đếm` (hàng bán/nhập sau lúc đếm giữ nguyên), một lần cho cả phiên. SKU
 * chưa đếm không bị đụng. Chênh âm làm tồn hiện tại xuống dưới 0 (đã bán hết phần còn lại) → ghi tới 0.
 */
final class StockCountService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly NumberSequences $sequences,
        private readonly CurrentContext $context,
        private readonly AuditLogger $audit,
    ) {}

    public function start(Location $location, ?string $note): StockCount
    {
        if (! $location->managesOnHand()) {
            throw ValidationException::withMessages(['location_id' => __('inventory::messages.external_authority', ['authority' => $location->stock_authority])]);
        }

        return DB::transaction(function () use ($location, $note): StockCount {
            $actor = $this->context->has() ? $this->context->actor() : null;
            $period = now(StoreClock::timezone())->format('ym');
            $count = StockCount::query()->create([
                'number' => sprintf('KK%s-%04d', $period, $this->sequences->next('stock_count', $period)),
                'location_id' => $location->id, 'note' => trim((string) $note) === '' ? null : trim((string) $note), 'status' => StockCount::COUNTING,
                'created_by_type' => $actor?->type->value, 'created_by_id' => $actor?->id,
            ]);
            $this->audit->record('inventory.count.started', 'stock_count', $count->id, ['number' => $count->number, 'location_id' => $location->id]);

            return $count;
        });
    }

    /**
     * Ghi số đếm: `add` cộng thêm (quét từng món), `set` đặt lại số đếm của SKU. Lưu tồn hệ thống tại thời điểm này.
     *
     * @param  array<int, int>  $quantities  variant id => số lượng
     */
    public function record(StockCount $count, array $quantities, bool $add): void
    {
        DB::transaction(function () use ($count, $quantities, $add): void {
            $count = $this->locked($count);
            $this->ledger->ensure(array_map(fn (int $variantId): array => [$count->location_id, $variantId], array_keys($quantities)));
            $onHand = DB::table('stock_levels')->where('location_id', $count->location_id)->whereIn('variant_id', array_keys($quantities))->pluck('on_hand', 'variant_id');
            foreach ($quantities as $variantId => $quantity) {
                $line = StockCountLine::query()->firstOrNew(['stock_count_id' => $count->id, 'variant_id' => $variantId]);
                $line->fill([
                    'counted' => max(0, ($add ? (int) $line->counted : 0) + $quantity),
                    'on_hand_at_count' => (int) ($onHand[$variantId] ?? 0),
                    'counted_at' => now(),
                ])->save();
            }
        });
    }

    public function removeLine(StockCount $count, int $variantId): void
    {
        DB::transaction(function () use ($count, $variantId): void {
            $count = $this->locked($count);
            StockCountLine::query()->where('stock_count_id', $count->id)->where('variant_id', $variantId)->delete();
        });
    }

    /**
     * Chênh lệch nếu áp dụng bây giờ.
     *
     * @return list<array{variant_id: int, counted: int, on_hand_at_count: int, on_hand: int, delta: int, new_on_hand: int}>
     */
    public function preview(StockCount $count): array
    {
        $lines = $count->lines()->get();
        $onHand = DB::table('stock_levels')->where('location_id', $count->location_id)->whereIn('variant_id', $lines->pluck('variant_id'))->pluck('on_hand', 'variant_id');

        return $lines->map(function (StockCountLine $line) use ($onHand): array {
            $current = (int) ($onHand[$line->variant_id] ?? 0);
            $delta = max(-$current, $line->variance());

            return ['variant_id' => $line->variant_id, 'counted' => $line->counted, 'on_hand_at_count' => $line->on_hand_at_count, 'on_hand' => $current, 'delta' => $delta, 'new_on_hand' => $current + $delta];
        })->values()->all();
    }

    /**
     * Áp dụng phiên: ghi phần chênh của mọi SKU đã đếm trong một transaction (khoá dòng tồn theo thứ tự cố định).
     *
     * @return array{changed: int, unchanged: int}
     */
    public function apply(StockCount $count): array
    {
        return DB::transaction(function () use ($count): array {
            $count = $this->locked($count);
            $lines = $count->lines()->get();
            $records = $this->ledger->lock($lines->map(fn (StockCountLine $line): array => [$count->location_id, $line->variant_id])->all(), createMissing: true);
            $reason = __('inventory::messages.count_reason', ['number' => $count->number]);
            $changed = [];
            foreach ($lines as $line) {
                $record = $records["{$count->location_id}:{$line->variant_id}"];
                $delta = max(-$record->on_hand, $line->variance());
                $line->forceFill(['applied_delta' => $delta])->save();
                if ($delta === 0) {
                    continue;
                }
                $this->ledger->save($record, $record->toDomain()->adjust($delta), MovementType::Adjust, $reason, $count->number);
                event(new StockAdjusted($count->location_id, $line->variant_id, $delta, $reason));
                $changed[] = $line->variant_id;
            }
            $count->forceFill(['status' => StockCount::APPLIED, 'applied_at' => now()])->save();
            $this->audit->record('inventory.count.applied', 'stock_count', $count->id, ['number' => $count->number, 'lines' => $lines->count(), 'changed' => count($changed)]);
            if ($changed !== []) {
                event(new AvailabilityChanged($changed));
            }

            return ['changed' => count($changed), 'unchanged' => $lines->count() - count($changed)];
        }, attempts: 3);
    }

    public function cancel(StockCount $count): void
    {
        DB::transaction(function () use ($count): void {
            $count = $this->locked($count);
            $count->forceFill(['status' => StockCount::CANCELLED])->save();
            $this->audit->record('inventory.count.cancelled', 'stock_count', $count->id, ['number' => $count->number]);
        });
    }

    private function locked(StockCount $count): StockCount
    {
        $count = StockCount::query()->whereKey($count->id)->lockForUpdate()->firstOrFail();
        if ($count->status !== StockCount::COUNTING) {
            throw ValidationException::withMessages(['count' => __('inventory::messages.count_closed', ['number' => $count->number])]);
        }

        return $count;
    }
}
