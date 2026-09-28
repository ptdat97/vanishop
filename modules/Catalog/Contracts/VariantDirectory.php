<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\VariantData;

/**
 * Service contract: tra variant cho các module khác (Pricing, Inventory, Ordering…). Lọc theo phạm vi brand.
 */
interface VariantDirectory
{
    /**
     * Mọi variant (cả ngừng bán) của một style trong brand, theo thứ tự màu → size.
     *
     * @return list<VariantData>
     */
    public function ofStyleCode(int $brandId, string $styleCode): array;

    /**
     * @param  list<int>  $variantIds
     * @return array<int, VariantData> id => data (id không tồn tại/ngoài phạm vi thì không có mặt)
     */
    public function find(array $variantIds): array;
}
