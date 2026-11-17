<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi(PluginApi::EXPERIMENTAL)]
final readonly class IncomingStockData
{
    public function __construct(
        public int $variantId,
        public int $quantity,
        public ?string $expectedOn,
        public string $source,
    ) {}
}
