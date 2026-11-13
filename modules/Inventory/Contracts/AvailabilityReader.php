<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Service contract: số lượng có thể bán (ATS) của variant.
 */
interface AvailabilityReader
{
    /** ATS của SKU thuộc sản phẩm không theo dõi tồn (luôn bán được) — đủ lớn cho mọi so sánh số lượng, không hiển thị ra ngoài. */
    public const UNLIMITED = 1_000_000;

    /**
     * @param  list<int>  $variantIds
     * @return array<int, int> variant id => ATS (variant không có tồn → 0; sản phẩm không theo dõi tồn → UNLIMITED)
     */
    public function forVariants(array $variantIds): array;
}
