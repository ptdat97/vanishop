<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Contracts\BackorderPolicy;
use Modules\Inventory\Contracts\Backorders;
use Modules\Inventory\Contracts\Data\BackorderAllowance;
use Modules\Inventory\Domain\ReservationStatus;
use Throwable;

/**
 * Đặt trước có giới hạn (ADR-047): gộp hạn mức của các `BackorderPolicy` (lấy lớn nhất), chỉ cho SKU có theo dõi tồn và
 * khi có kho bù tồn. Số chờ hàng = max(0, reserved + safety − on_hand) tại kho bù tồn — không có bộ đếm riêng.
 */
final class BackorderService implements Backorders
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly VariantDirectory $variants,
        private readonly OnlineLocations $locations,
    ) {}

    /**
     * Hạn mức theo SKU (đã lọc SKU không theo dõi tồn). Policy lỗi → bỏ qua, không chặn bán hàng thật.
     *
     * @param  list<int>  $variantIds
     * @return array<int, BackorderAllowance>
     */
    public function allowances(array $variantIds): array
    {
        $policies = $this->extensions->implementations(BackorderPolicy::TAG, BackorderPolicy::class, fn (BackorderPolicy $policy): string => $policy::class);
        $variantIds = array_values(array_unique(array_map('intval', $variantIds)));
        if ($policies === [] || $variantIds === []) {
            return [];
        }

        $result = [];
        foreach ($policies as $policy) {
            try {
                $allowances = $policy->allowances($variantIds);
            } catch (Throwable $exception) {
                Log::warning('BackorderPolicy lỗi, bỏ qua.', ['policy' => $policy::class, 'error' => $exception->getMessage()]);

                continue;
            }
            foreach ($allowances as $variantId => $allowance) {
                if ($allowance instanceof BackorderAllowance && $allowance->limit > 0 && in_array((int) $variantId, $variantIds, true)
                    && ($allowance->limit > ($result[(int) $variantId]->limit ?? 0))) {
                    $result[(int) $variantId] = $allowance;
                }
            }
        }
        foreach ($this->variants->untrackedInventory(array_keys($result)) as $untracked) {
            unset($result[(int) $untracked]);
        }

        return $result;
    }

    public function status(array $variantIds): array
    {
        $location = $this->locations->topUpLocation();
        $allowances = $location === null ? [] : $this->allowances($variantIds);
        if ($allowances === []) {
            return [];
        }

        $levels = DB::table('stock_levels')->where('location_id', $location)->whereIn('variant_id', array_keys($allowances))
            ->get(['variant_id', 'on_hand', 'reserved', 'safety_stock'])->keyBy('variant_id');
        $result = [];
        foreach ($allowances as $variantId => $allowance) {
            $level = $levels[$variantId] ?? null;
            $waiting = $level === null ? 0 : max(0, (int) $level->reserved + (int) $level->safety_stock - (int) $level->on_hand);
            $result[$variantId] = [
                'limit' => $allowance->limit, 'waiting' => $waiting, 'remaining' => max(0, $allowance->limit - $waiting), 'expected_on' => $allowance->expectedOn,
            ];
        }

        return $result;
    }

    public function waitingFor(string $reservationKey): array
    {
        $location = $this->locations->topUpLocation();
        if ($location === null) {
            return [];
        }
        $variantIds = DB::table('stock_reservations')->where('reservation_key', $reservationKey)->where('status', ReservationStatus::Active->value)
            ->where('location_id', $location)->distinct()->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();
        if ($variantIds === []) {
            return [];
        }

        $levels = DB::table('stock_levels')->where('location_id', $location)->whereIn('variant_id', $variantIds)->get()->keyBy('variant_id');
        $result = [];
        foreach ($variantIds as $variantId) {
            $level = $levels[$variantId] ?? null;
            $remaining = $level === null ? 0 : max(0, (int) $level->reserved + (int) $level->safety_stock - (int) $level->on_hand);
            if ($remaining === 0) {
                continue;
            }
            // Hàng về lấp cho lần giữ hàng cũ trước → phần chờ thuộc về các lần giữ mới nhất.
            $rows = DB::table('stock_reservations')->where('location_id', $location)->where('variant_id', $variantId)
                ->where('status', ReservationStatus::Active->value)->orderByDesc('id')->get(['reservation_key', 'quantity']);
            foreach ($rows as $row) {
                if ($remaining === 0) {
                    break;
                }
                $take = min($remaining, (int) $row->quantity);
                $remaining -= $take;
                if ($row->reservation_key === $reservationKey) {
                    $result[$variantId] = ($result[$variantId] ?? 0) + $take;
                }
            }
        }

        return $result;
    }
}
