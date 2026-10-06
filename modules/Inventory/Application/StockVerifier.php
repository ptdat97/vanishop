<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Domain\ReservationStatus;
use Modules\Inventory\Domain\StockLevel;

/**
 * Đối soát tồn kho với chính sổ của nó (bất biến của Inventory):
 * - `reserved` = tổng hàng giữ đang active (suy ra được → sửa được bằng --repair-reserved, ghi biến động `reconcile`);
 * - on_hand/reserved = giá trị "sau" của biến động cuối (lệch = có chỗ sửa tồn ngoài sổ → chỉ báo, cần kiểm kê);
 * - không âm.
 */
final class StockVerifier
{
    private const CHUNK = 500;

    public function __construct(private readonly StockLedger $ledger) {}

    /**
     * @return array{checked: int, issues: list<array{location_id: int, variant_id: int, issue: string, expected: int, actual: int, repaired: bool}>, repaired: int}
     */
    public function verify(bool $repairReserved = false): array
    {
        $checked = $repaired = 0;
        $issues = [];

        DB::table('stock_levels')->orderBy('location_id')->orderBy('variant_id')->select(['location_id', 'variant_id', 'on_hand', 'reserved'])
            ->chunk(self::CHUNK, function ($levels) use (&$checked, &$issues, &$repaired, $repairReserved): void {
                $variantIds = $levels->pluck('variant_id')->unique()->all();
                $active = DB::table('stock_reservations')->where('status', ReservationStatus::Active->value)->whereIn('variant_id', $variantIds)
                    ->groupBy('location_id', 'variant_id')->selectRaw('location_id, variant_id, sum(quantity) as total')->get()
                    ->keyBy(fn ($row): string => "{$row->location_id}:{$row->variant_id}");
                $last = DB::table('stock_movements')->whereIn('id', DB::table('stock_movements')->whereIn('variant_id', $variantIds)->groupBy('location_id', 'variant_id')->selectRaw('max(id)'))
                    ->get(['location_id', 'variant_id', 'on_hand_after', 'reserved_after'])
                    ->keyBy(fn ($row): string => "{$row->location_id}:{$row->variant_id}");

                foreach ($levels as $level) {
                    $checked++;
                    $key = "{$level->location_id}:{$level->variant_id}";
                    $issue = function (string $type, int $expected, int $actual, bool $wasRepaired = false) use (&$issues, $level): void {
                        $issues[] = ['location_id' => (int) $level->location_id, 'variant_id' => (int) $level->variant_id, 'issue' => $type, 'expected' => $expected, 'actual' => $actual, 'repaired' => $wasRepaired];
                    };

                    if ((int) $level->on_hand < 0) {
                        $issue('negative_on_hand', 0, (int) $level->on_hand);
                    }
                    $expectedReserved = (int) ($active[$key]->total ?? 0);
                    if ((int) $level->reserved !== $expectedReserved) {
                        $issue('reserved_mismatch', $expectedReserved, (int) $level->reserved);
                        if ($repairReserved && $this->repairReserved((int) $level->location_id, (int) $level->variant_id)) {
                            $repaired++;
                            $issues[array_key_last($issues)]['repaired'] = true;

                            continue; // biến động reconcile vừa ghi là mốc mới của sổ
                        }
                    }
                    if (isset($last[$key])) {
                        if ((int) $last[$key]->on_hand_after !== (int) $level->on_hand) {
                            $issue('on_hand_off_ledger', (int) $last[$key]->on_hand_after, (int) $level->on_hand);
                        }
                        if ((int) $last[$key]->reserved_after !== (int) $level->reserved && (int) $level->reserved === $expectedReserved) {
                            $issue('reserved_off_ledger', (int) $last[$key]->reserved_after, (int) $level->reserved);
                        }
                    }
                }
            });

        return ['checked' => $checked, 'issues' => $issues, 'repaired' => $repaired];
    }

    private function repairReserved(int $locationId, int $variantId): bool
    {
        return DB::transaction(function () use ($locationId, $variantId): bool {
            $record = $this->ledger->lock([[$locationId, $variantId]])["{$locationId}:{$variantId}"] ?? null;
            if ($record === null) {
                return false;
            }
            // Tính lại trong khoá: hàng giữ có thể vừa đổi từ lúc quét.
            $active = (int) DB::table('stock_reservations')->where(['location_id' => $locationId, 'variant_id' => $variantId, 'status' => ReservationStatus::Active->value])->sum('quantity');
            $current = $record->toDomain();
            if ($current->reserved === $active) {
                return false;
            }

            $this->ledger->save($record, new StockLevel($locationId, $variantId, $current->onHand, $active, $current->safetyStock, $current->syncVersion), MovementType::Reconcile, 'verify_repair_reserved');

            return true;
        });
    }
}
