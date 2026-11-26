<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Shared\Contracts\PluginApi;

/** Giá cá nhân phục vụ nội dung riêng gửi cho chính khách hàng. */
#[PluginApi]
interface CustomerProductPrices
{
    /**
     * @param  list<int>  $productIds  tối đa ProductCards::MAX_IDS
     * @return array{group_id: ?int, prices: array<int, array{amount: int, formatted: string}>}
     */
    public function forCustomer(array $productIds, int $customerId, string $locale): array;
}
