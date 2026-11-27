<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/** Tình trạng một suất (thanh "đã bán x%", "còn N suất"). */
#[PluginApi]
final readonly class PriceQuotaStatus
{
    public function __construct(
        public string $scope,
        public int $scopeId,
        public ?int $limit,
        public int $sold,
        public ?int $perCustomerLimit,
    ) {}

    public function remaining(): ?int
    {
        return $this->limit === null ? null : max(0, $this->limit - $this->sold);
    }
}
