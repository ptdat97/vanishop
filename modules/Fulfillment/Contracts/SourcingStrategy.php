<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Fulfillment\Contracts\Data\AllocationProposal;
use Modules\Fulfillment\Contracts\Data\SourcingRequest;

/**
 * Extension point (tag `vani.fulfillment.sourcing`): chọn kho giao cho từng dòng.
 * Mặc định `reserved_locations`: giao từ đúng location đã giữ hàng lúc đặt (một shipment mỗi location).
 * Hiện Core chỉ chấp nhận đề xuất khớp với hàng đang giữ (chưa có chuyển giữ hàng giữa kho).
 */
interface SourcingStrategy
{
    /** Tag extension point: plugin đóng góp qua `contribute(SourcingStrategy::TAG, …)`. */
    public const TAG = 'vani.fulfillment.sourcing';

    public function code(): string;

    /**
     * @return list<AllocationProposal>
     */
    public function allocate(SourcingRequest $request): array;
}
