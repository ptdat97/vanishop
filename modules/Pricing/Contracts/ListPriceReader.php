<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.119): giá niêm yết (bảng giá `base`) theo variant — mốc để plugin tính giá khuyến mãi (giảm %, giảm
 * tiền) cho bảng giá của mình (`ManagedPriceLists`). Đổi giá niêm yết phát `PriceChanged` với id bảng giá `base`.
 */
#[PluginApi]
interface ListPriceReader
{
    /**
     * @param  list<int>  $variantIds
     * @return array<int, int> variant id => giá niêm yết (minor unit); variant chưa có giá niêm yết thì vắng
     */
    public function forVariants(array $variantIds): array;

    /** Id bảng giá niêm yết (`base`); chưa có → null. */
    public function listId(): ?int;
}
