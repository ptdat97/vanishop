<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Shared\Contracts\PluginApi;
use RuntimeException;

#[PluginApi]
final class InvalidCarrierEvent extends RuntimeException {}
