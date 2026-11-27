<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Contracts\Data\PriceQuotaRule;
use Modules\Pricing\Contracts\Data\PriceQuotaStatus;
use Modules\Pricing\Contracts\Data\QuotaLine;
use Modules\Pricing\Contracts\Data\QuotaViolation;
use Modules\Pricing\Contracts\PriceQuotaExceeded;
use Modules\Pricing\Contracts\PriceQuotas;

/**
 * Suất giá ({@see PriceQuotas}). Một dòng hàng có thể thuộc nhiều suất của cùng bảng giá (cả bảng + theo sản phẩm + theo
 * variant): phải còn đủ ở mọi suất.
 *
 * @phpstan-type QuotaRow object{id: int, price_list_id: int, scope: string, scope_id: int, quota_limit: int|null, sold: int, per_customer_limit: int|null}
 */
final class PriceQuotaService implements PriceQuotas
{
    public function __construct(private readonly VariantDirectory $variants) {}

    public function define(int $priceListId, array $rules): void
    {
        $keys = [];
        foreach ($rules as $index => $rule) {
            $valid = in_array($rule->scope, [PriceQuotaRule::SCOPE_LIST, PriceQuotaRule::SCOPE_PRODUCT, PriceQuotaRule::SCOPE_VARIANT], true)
                && ($rule->scope === PriceQuotaRule::SCOPE_LIST ? $rule->scopeId === 0 : $rule->scopeId > 0)
                && ($rule->limit === null || $rule->limit >= 0) && ($rule->perCustomerLimit === null || $rule->perCustomerLimit >= 1)
                && ($rule->limit !== null || $rule->perCustomerLimit !== null);
            if (! $valid) {
                throw ValidationException::withMessages(["quotas.{$index}" => __('pricing::messages.quota_invalid')]);
            }
            $keys[$rule->scope.':'.$rule->scopeId] = $rule;
        }

        DB::transaction(function () use ($priceListId, $keys): void {
            $existing = DB::table('price_quotas')->where('price_list_id', $priceListId)->lockForUpdate()->get()->keyBy(fn (object $row): string => $row->scope.':'.$row->scope_id);
            $stale = $existing->keys()->diff(array_keys($keys))->map(fn (string $key): int => (int) $existing[$key]->id)->values()->all();
            if ($stale !== []) {
                DB::table('price_quotas')->whereIn('id', $stale)->delete();
            }
            foreach ($keys as $key => $rule) {
                $values = ['quota_limit' => $rule->limit, 'per_customer_limit' => $rule->perCustomerLimit, 'updated_at' => now()];
                if (isset($existing[$key])) {
                    // Giảm suất dưới số đã bán: giữ số đã bán, không bán thêm.
                    $sold = (int) $existing[$key]->sold;
                    DB::table('price_quotas')->where('id', $existing[$key]->id)->update([...$values, 'quota_limit' => $rule->limit === null ? null : max($rule->limit, $sold)]);
                } else {
                    DB::table('price_quotas')->insert([...$values, 'price_list_id' => $priceListId, 'scope' => $rule->scope, 'scope_id' => $rule->scopeId, 'sold' => 0, 'created_at' => now()]);
                }
            }
        });
    }

    public function status(int $priceListId): array
    {
        return DB::table('price_quotas')->where('price_list_id', $priceListId)->orderBy('id')->get()
            ->map(fn (object $row): PriceQuotaStatus => new PriceQuotaStatus((string) $row->scope, (int) $row->scope_id,
                $row->quota_limit === null ? null : (int) $row->quota_limit, (int) $row->sold, $row->per_customer_limit === null ? null : (int) $row->per_customer_limit))
            ->all();
    }

    public function check(array $lines, ?int $customerId): array
    {
        $demand = $this->demand($lines);
        if ($demand === []) {
            return [];
        }
        $used = $customerId === null ? [] : $this->customerUsed(array_keys($demand), $customerId);

        $violations = [];
        foreach ($demand as $quotaId => ['quota' => $quota, 'quantity' => $quantity, 'variants' => $variants]) {
            $remaining = $quota->quota_limit === null ? PHP_INT_MAX : max(0, (int) $quota->quota_limit - (int) $quota->sold);
            $personal = $customerId === null || $quota->per_customer_limit === null ? PHP_INT_MAX : max(0, (int) $quota->per_customer_limit - ($used[$quotaId] ?? 0));
            if ($quantity <= min($remaining, $personal)) {
                continue;
            }
            $violations[] = $personal < $remaining
                ? new QuotaViolation($variants, 'customer_limit', $personal, __('pricing::messages.quota_customer_limit', ['limit' => (int) $quota->per_customer_limit, 'allowed' => $personal]))
                : new QuotaViolation($variants, 'sold_out', $remaining, __('pricing::messages.quota_sold_out', ['allowed' => $remaining]));
        }

        return $violations;
    }

    public function consume(int $orderId, ?int $customerId, array $lines): void
    {
        foreach ($this->demand($lines) as $quotaId => ['quota' => $quota, 'quantity' => $quantity, 'byVariant' => $byVariant]) {
            $updated = DB::table('price_quotas')->where('id', $quotaId)
                ->where(fn ($query) => $query->whereNull('quota_limit')->orWhereRaw('sold + ? <= quota_limit', [$quantity]))
                ->increment('sold', $quantity, ['updated_at' => now()]);
            if ($updated === 0) {
                throw new PriceQuotaExceeded;
            }
            if ($customerId !== null) {
                DB::table('price_quota_customers')->insertOrIgnore(['price_quota_id' => $quotaId, 'customer_id' => $customerId, 'used' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $updated = DB::table('price_quota_customers')->where('price_quota_id', $quotaId)->where('customer_id', $customerId)
                    ->when($quota->per_customer_limit !== null, fn ($query) => $query->whereRaw('used + ? <= ?', [$quantity, (int) $quota->per_customer_limit]))
                    ->increment('used', $quantity, ['updated_at' => now()]);
                if ($updated === 0) {
                    throw new PriceQuotaExceeded;
                }
            }
            foreach ($byVariant as $variantId => $variantQuantity) {
                DB::table('price_quota_usages')->insert([
                    'price_quota_id' => $quotaId, 'order_id' => $orderId, 'variant_id' => $variantId, 'customer_id' => $customerId,
                    'quantity' => $variantQuantity, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function revert(int $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $usages = DB::table('price_quota_usages')->where('order_id', $orderId)->where('quantity', '>', 0)->lockForUpdate()->get();
            foreach ($usages as $usage) {
                $this->give($usage, (int) $usage->quantity);
            }
        });
    }

    public function release(int $orderId, array $quantities): void
    {
        DB::transaction(function () use ($orderId, $quantities): void {
            $usages = DB::table('price_quota_usages')->where('order_id', $orderId)->whereIn('variant_id', array_keys($quantities))->where('quantity', '>', 0)->lockForUpdate()->get();
            foreach ($usages as $usage) {
                $this->give($usage, min((int) $usage->quantity, (int) ($quantities[(int) $usage->variant_id] ?? 0)));
            }
        });
    }

    /**
     * Mức giá (bảng giá, variant) không còn bán được theo giá này: hết suất ở một suất phủ variant, hoặc khách đã dùng hết lượt.
     *
     * @param  array<int, list<int>>  $variantsByList  bảng giá id => variant id đang xét
     * @return array<string, true> "<bảng giá id>:<variant id>" cần bỏ
     */
    public function exhausted(array $variantsByList, ?int $customerId): array
    {
        if ($variantsByList === []) {
            return [];
        }
        $quotas = DB::table('price_quotas')->whereIn('price_list_id', array_keys($variantsByList))->get();
        if ($quotas->isEmpty()) {
            return [];
        }
        $used = $customerId === null ? [] : $this->customerUsed($quotas->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(), $customerId);
        $products = $this->productsOf(array_merge(...array_values($variantsByList)));

        $excluded = [];
        foreach ($quotas as $quota) {
            $soldOut = $quota->quota_limit !== null && (int) $quota->sold >= (int) $quota->quota_limit;
            $limitReached = $quota->per_customer_limit !== null && ($used[(int) $quota->id] ?? 0) >= (int) $quota->per_customer_limit;
            if (! $soldOut && ! $limitReached) {
                continue;
            }
            foreach ($variantsByList[(int) $quota->price_list_id] as $variantId) {
                if ($this->covers($quota, $variantId, $products[$variantId] ?? 0)) {
                    $excluded[$quota->price_list_id.':'.$variantId] = true;
                }
            }
        }

        return $excluded;
    }

    /**
     * Nhu cầu theo suất: dòng có giá từ bảng giá có suất → cộng số lượng vào mọi suất phủ dòng.
     *
     * @param  list<QuotaLine>  $lines
     * @return array<int, array{quota: object, quantity: int, variants: list<int>, byVariant: array<int, int>}>
     */
    private function demand(array $lines): array
    {
        $codes = array_values(array_unique(array_filter(array_map(fn (QuotaLine $line): ?string => $line->priceListCode, $lines))));
        if ($codes === []) {
            return [];
        }
        $quotas = DB::table('price_quotas')->join('price_lists', 'price_lists.id', '=', 'price_quotas.price_list_id')
            ->whereIn('price_lists.code', $codes)->get(['price_quotas.*', 'price_lists.code as list_code']);
        $demand = [];
        foreach ($lines as $line) {
            foreach ($quotas as $quota) {
                if ($quota->list_code !== $line->priceListCode || ! $this->covers($quota, $line->variantId, $line->productId)) {
                    continue;
                }
                $id = (int) $quota->id;
                $demand[$id] ??= ['quota' => $quota, 'quantity' => 0, 'variants' => [], 'byVariant' => []];
                $demand[$id]['quantity'] += $line->quantity;
                $demand[$id]['variants'][] = $line->variantId;
                $demand[$id]['byVariant'][$line->variantId] = ($demand[$id]['byVariant'][$line->variantId] ?? 0) + $line->quantity;
            }
        }
        ksort($demand);

        return $demand;
    }

    private function covers(object $quota, int $variantId, int $productId): bool
    {
        return match ((string) $quota->scope) {
            PriceQuotaRule::SCOPE_LIST => true,
            PriceQuotaRule::SCOPE_PRODUCT => (int) $quota->scope_id === $productId,
            default => (int) $quota->scope_id === $variantId,
        };
    }

    /**
     * @param  list<int>  $quotaIds
     * @return array<int, int>
     */
    private function customerUsed(array $quotaIds, int $customerId): array
    {
        return DB::table('price_quota_customers')->whereIn('price_quota_id', $quotaIds)->where('customer_id', $customerId)
            ->pluck('used', 'price_quota_id')->map(fn (mixed $used): int => (int) $used)->all();
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    private function productsOf(array $variantIds): array
    {
        return array_map(fn (VariantData $variant): int => $variant->productId, $this->variants->find(array_values(array_unique($variantIds))));
    }

    private function give(object $usage, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }
        DB::table('price_quota_usages')->where('id', $usage->id)->update(['quantity' => (int) $usage->quantity - $quantity, 'updated_at' => now()]);
        DB::table('price_quotas')->where('id', $usage->price_quota_id)
            ->update(['sold' => DB::raw('CASE WHEN sold > '.$quantity.' THEN sold - '.$quantity.' ELSE 0 END'), 'updated_at' => now()]);
        if ($usage->customer_id !== null) {
            DB::table('price_quota_customers')->where('price_quota_id', $usage->price_quota_id)->where('customer_id', $usage->customer_id)
                ->update(['used' => DB::raw('CASE WHEN used > '.$quantity.' THEN used - '.$quantity.' ELSE 0 END'), 'updated_at' => now()]);
        }
    }
}
