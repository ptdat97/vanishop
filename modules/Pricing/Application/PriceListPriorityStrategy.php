<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\ResolvedPrice;
use Modules\Pricing\Contracts\PricingStrategy;
use Modules\Pricing\Domain\PriceCandidate;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Domain\PriceSelection;
use Modules\Shared\Domain\Money\Money;

/**
 * Chiến lược mặc định: bảng giá đang hiệu lực của kênh → PriceSelection (priority, giá thấp nhất, giá gốc).
 * 1 truy vấn cho cả lô variant (dùng được cho trang danh sách).
 */
final class PriceListPriorityStrategy implements PricingStrategy
{
    public function code(): string
    {
        return 'price_list_priority';
    }

    public function resolve(array $variantIds, PricingContext $context): array
    {
        if ($variantIds === []) {
            return [];
        }

        $now = Carbon::createFromTimestamp($context->now);

        $rows = DB::table('prices')
            ->join('price_lists', 'price_lists.id', '=', 'prices.price_list_id')
            ->join('channel_price_lists', 'channel_price_lists.price_list_id', '=', 'price_lists.id')
            ->where('channel_price_lists.channel_id', $context->channelId)
            ->where(fn ($query) => $query->whereNull('channel_price_lists.customer_group_id')
                ->when($context->customerGroupId !== null, fn ($q) => $q->orWhere('channel_price_lists.customer_group_id', $context->customerGroupId)))
            ->where('price_lists.status', 'active')
            ->where(fn ($query) => $query->whereNull('price_lists.starts_at')->orWhere('price_lists.starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('price_lists.ends_at')->orWhere('price_lists.ends_at', '>', $now))
            ->whereIn('prices.variant_id', $variantIds)
            ->where('prices.min_qty', 1)
            ->get(['prices.variant_id', 'prices.amount', 'prices.compare_at_amount', 'price_lists.id as list_id', 'price_lists.code', 'price_lists.type', 'price_lists.priority', 'price_lists.currency_code']);

        $resolved = [];
        foreach ($rows->groupBy('variant_id') as $variantId => $group) {
            $selected = PriceSelection::choose($group->map(fn (object $row): PriceCandidate => new PriceCandidate(
                priceListId: (int) $row->list_id,
                priceListCode: (string) $row->code,
                type: PriceListType::from((string) $row->type),
                priority: (int) $row->priority,
                amount: Money::of((int) $row->amount, (string) $row->currency_code),
                compareAt: $row->compare_at_amount === null ? null : Money::of((int) $row->compare_at_amount, (string) $row->currency_code),
            ))->values()->all());

            if ($selected !== null) {
                $resolved[(int) $variantId] = new ResolvedPrice((int) $variantId, $selected->amount, $selected->compareAt, $selected->discountPercent(), $selected->priceListCode);
            }
        }

        return $resolved;
    }
}
