<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Pricing\Contracts\Data\DiscountCap;
use Modules\Pricing\Contracts\Data\ManagedPriceList;
use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Tenancy\Contracts\Settings;

final class ManagedPriceListService implements ManagedPriceLists
{
    public function __construct(
        private readonly PriceListService $lists,
        private readonly Settings $settings,
    ) {}

    public function sync(string $owner, string $key, ManagedPriceList $list): int
    {
        if (! in_array($list->type, [ManagedPriceList::TYPE_SALE, ManagedPriceList::TYPE_MEMBER], true)) {
            throw ValidationException::withMessages(['type' => __('pricing::messages.managed_type_invalid')]);
        }
        if ($list->type === ManagedPriceList::TYPE_MEMBER && $list->customerGroupId === null) {
            throw ValidationException::withMessages(['customer_group_id' => __('pricing::messages.managed_group_required')]);
        }
        foreach ($list->prices as $variantId => $amount) {
            if ($amount < 0 || $amount > PriceListService::MAX_AMOUNT) {
                throw ValidationException::withMessages(["prices.{$variantId}" => __('pricing::messages.amount_invalid')]);
            }
        }
        $this->assertWithinCap($list->prices);

        return DB::transaction(function () use ($owner, $key, $list): int {
            $existing = PriceList::query()->where('managed_by', $owner)->where('managed_key', $key)->lockForUpdate()->first();
            $saved = $this->lists->save([
                'code' => $existing->code ?? $this->code($owner, $key),
                'name' => $list->name,
                'type' => $list->type,
                'customer_group_id' => $list->type === ManagedPriceList::TYPE_MEMBER ? $list->customerGroupId : null,
                'priority' => $list->priority,
                'starts_at' => $list->startsAt === null ? null : Carbon::createFromTimestamp($list->startsAt)->toDateTimeString(),
                'ends_at' => $list->endsAt === null ? null : Carbon::createFromTimestamp($list->endsAt)->toDateTimeString(),
                'status' => $list->active ? 'active' : 'inactive',
                'managed_by' => $owner,
                'managed_key' => $key,
                'managed_route' => $list->editRoute,
            ], $existing, $existing?->lock_version);

            $rows = [];
            foreach ($list->prices as $variantId => $amount) {
                $rows[] = ['variant_id' => (int) $variantId, 'amount' => $amount, 'compare_at_amount' => null];
            }
            $stale = Price::query()->where('price_list_id', $saved->id)->where('min_qty', 1)->whereNotIn('variant_id', array_keys($list->prices))->pluck('variant_id');
            foreach ($stale as $variantId) {
                $rows[] = ['variant_id' => (int) $variantId, 'amount' => null, 'compare_at_amount' => null];
            }
            if ($rows !== []) {
                $this->lists->setPrices($saved, $rows);
            }

            return $saved->id;
        });
    }

    public function remove(string $owner, string $key): void
    {
        $list = PriceList::query()->where('managed_by', $owner)->where('managed_key', $key)->first();
        if ($list !== null) {
            $this->lists->delete($list);
        }
    }

    public function lists(string $owner): array
    {
        return PriceList::query()->where('managed_by', $owner)->whereNotNull('managed_key')->get(['id', 'code', 'managed_key'])
            ->mapWithKeys(fn (PriceList $list): array => [(string) $list->managed_key => ['id' => $list->id, 'code' => $list->code]])->all();
    }

    /** Mã cố định theo (plugin, khoá); trùng mã bảng giá khác thì thêm hậu tố. */
    private function code(string $owner, string $key): string
    {
        $base = Str::limit(Str::slug(str_replace('.', '-', $owner).'-'.$key), 56, '');
        $code = $base;
        for ($suffix = 2; PriceList::query()->where('code', $code)->exists(); $suffix++) {
            $code = "{$base}-{$suffix}";
        }

        return $code;
    }

    /**
     * Giá không thấp hơn mức trần giảm so với giá niêm yết (Cài đặt → Bán hàng & thuế, VN 50%).
     *
     * @param  array<int, int>  $prices
     */
    private function assertWithinCap(array $prices): void
    {
        $bp = DiscountCap::basisPoints($this->settings->get('core', DiscountCap::SETTING));
        if ($bp >= 10_000 || $prices === []) {
            return;
        }
        $base = Price::query()->join('price_lists', 'price_lists.id', '=', 'prices.price_list_id')
            ->where('price_lists.type', PriceListType::Base->value)->where('price_lists.code', ListPriceSync::BASE_CODE)->where('prices.min_qty', 1)
            ->whereIn('prices.variant_id', array_keys($prices))->pluck('prices.amount', 'prices.variant_id');
        foreach ($prices as $variantId => $amount) {
            $listAmount = (int) ($base[$variantId] ?? 0);
            $floor = $listAmount - intdiv($listAmount * $bp, 10_000);
            if ($listAmount > 0 && $amount < $floor) {
                throw ValidationException::withMessages(["prices.{$variantId}" => __('pricing::messages.below_discount_cap', ['percent' => intdiv($bp, 100)])]);
            }
        }
    }
}
