<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
final readonly class GatewayResult
{
    public function __construct(
        public bool $successful,
        public ?string $gatewayReference = null,
        public ?string $message = null,
    ) {}
}
