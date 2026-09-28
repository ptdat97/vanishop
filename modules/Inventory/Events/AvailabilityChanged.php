<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * ATS có thể đã đổi (giữ/bỏ giữ/xuất/điều chỉnh) — cache, search index, feed có thể cập nhật.
 */
final readonly class AvailabilityChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $variantIds
     */
    public function __construct(public array $variantIds) {}
}
