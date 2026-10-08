<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Customer\Contracts\CustomerSegments;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Giá thành viên của khách đang đăng nhập (roadmap Phase 8): trang công khai cache được hiển thị giá chung; JS/API hỏi
 * thêm ở đây, chỉ trả variant mà giá theo nhóm khách THẤP HƠN giá chung.
 */
final class MemberPrices
{
    public const MAX_VARIANTS = 100;

    public function __construct(
        private readonly PriceResolver $prices,
        private readonly CustomerSegments $segments,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * @param  list<int>  $variantIds
     * @return array{group: ?string, prices: array<int, array<string, mixed>>}
     */
    public function for(int $customerId, array $variantIds): array
    {
        $segment = $this->segments->segmentOf($customerId);
        $variantIds = array_slice(array_values(array_unique(array_filter($variantIds, fn (int $id): bool => $id > 0))), 0, self::MAX_VARIANTS);
        if ($segment->groupId === null || $variantIds === []) {
            return ['group' => $segment->groupName, 'prices' => []];
        }

        $now = now()->getTimestamp();
        $public = $this->prices->forVariants($variantIds, new PricingContext($now));
        $member = $this->prices->forVariants($variantIds, new PricingContext($now, $segment->groupId, $customerId));

        $result = [];
        foreach ($member as $variantId => $price) {
            $base = $public[$variantId] ?? null;
            if ($base === null || $price->amount->amount < $base->amount->amount) {
                $result[$variantId] = $this->money->toArray($price->amount);
            }
        }

        return ['group' => $segment->groupName, 'prices' => $result];
    }
}
