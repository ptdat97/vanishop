<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
final readonly class AllocationProposal
{
    /**
     * @param  array<int, int>  $lines  order_line_id => số lượng giao từ location này
     */
    public function __construct(
        public int $locationId,
        public array $lines,
    ) {}
}
