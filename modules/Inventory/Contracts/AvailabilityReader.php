<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

/**
 * Service contract: số lượng có thể bán (ATS) của variant trên một kênh.
 */
interface AvailabilityReader
{
    /**
     * @param  list<int>  $variantIds
     * @return array<int, int> variant id => ATS (variant không có tồn → 0)
     */
    public function forChannel(array $variantIds, int $channelId): array;
}
